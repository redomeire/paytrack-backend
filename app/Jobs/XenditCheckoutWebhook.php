<?php

namespace App\Jobs;

use App\Mail\PaymentFailed;
use App\Mail\PaymentSuccess;
use App\Models\bills;
use App\Models\notification;
use App\Models\notification_read;
use App\Models\notification_type;
use App\Models\payments;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class XenditCheckoutWebhook implements ShouldQueue
{
    use Queueable;

    protected array $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function handle(): void
    {
        try {
            Log::info('Webhook Xendit diterima. Payload:', $this->payload);

            $status = $this->payload['status'] ?? null;
            $invoiceId = $this->payload['external_id'] ?? null;

            if (!$status || !$invoiceId) {
                Log::error('Payload webhook tidak valid atau tidak memiliki status/invoice ID.', $this->payload);
                return;
            }

            $bill = bills::where('id', $invoiceId)->first();

            if ($bill) {
                if ($bill->status === 'paid') {
                    Log::warning("Tagihan #{$bill->id} sudah berstatus 'paid', mengabaikan webhook.");
                    return;
                }

                if ($status === 'PAID') {
                    payments::create([
                        'bill_id' => $bill->id,
                        'amount' => $this->payload['paid_amount'] ?? 0,
                        'currency' => $this->payload['currency'] ?? 'IDR',
                        'paid_date' => $this->payload['paid_at'] ? \Carbon\Carbon::parse($this->payload['paid_at']) : now(),
                        'due_date' => $bill->due_date ?? now(),
                        'payment_method' => $this->payload['payment_method'] ?? 'Xendit',
                        'payment_reference' => $this->payload['id'] ?? null,
                        'notes' => 'Payment via Xendit Webhook',
                    ]);

                    Log::info("Pembayaran untuk tagihan #{$bill->id} telah dibuat.", $bill->toArray());

                    $bill->status = 'paid';
                    $bill->save();
                    Log::info("Tagihan #{$bill->id} berhasil diperbarui menjadi 'paid'.");

                    if ($bill->user_id) {
                        $user = User::find($bill->user_id);
                        Mail::to($user->email)->send(new PaymentSuccess($bill));

                        $this->createNotification(
                            $bill->user_id,
                            $bill->id,
                            'Pembayaran Berhasil',
                            "Pembayaran untuk tagihan '{$bill->title}' sebesar {$bill->amount} telah berhasil.",
                            'Payment_Status_Success',
                            'Notifikasi ini dikirimkan ketika pembayaran tagihan berhasil.'
                        );
                    }
                } elseif ($status === 'EXPIRED') {
                    $bill->status = 'overdue';
                    $bill->save();
                    Log::warning("Tagihan #{$bill->id} kedaluwarsa.");

                    $this->createNotification(
                        $bill->user_id,
                        $bill->id,
                        'Tagihan Kedaluwarsa',
                        "Tagihan '{$bill->title}' telah kedaluwarsa dan belum dibayar.",
                        'Payment_Status_Expired',
                        'Notifikasi ini dikirimkan ketika tagihan telah kedaluwarsa tanpa pembayaran.'
                    );
                } elseif ($status === 'FAILED') {
                    $bill->status = 'failed';
                    $bill->save();

                    Log::warning("Tagihan #{$bill->id} gagal.");

                    if ($bill->user_id) {
                        $user = User::find($bill->user_id);
                        Mail::to($user->email)->send(new PaymentFailed($bill));
                    }

                    $this->createNotification(
                        $bill->user_id,
                        $bill->id,
                        'Pembayaran Gagal',
                        "Pembayaran untuk tagihan '{$bill->title}' gagal. Silakan coba lagi.",
                        'Payment_Status_Failed',
                        'Notifikasi ini dikirimkan ketika pembayaran tagihan gagal.'
                    );
                }
            } else {
                Log::warning("Tagihan dengan ID '{$invoiceId}' tidak ditemukan.");
            }
        } catch (\Throwable $th) {
            Log::error('Error processing Xendit webhook: ' . $th->getMessage(), $this->payload);
            throw $th;
        }
    }

    private function createNotification(
        $userId,
        $billId,
        $title,
        $message,
        $type = 'Payment_Status_Success',
        $description
    ): void {
        try {
            Log::info("Creating notification for bill ID {$billId} and user ID {$userId}");
            $notificationType = notification_type::firstOrCreate([
                'name' => $type,
            ], [
                'name' => $type,
                'description' => $description,
            ]);
            if (!$notificationType) {
                Log::error("Notification type ID {$typeId} not found.");
                return;
            }
            // create transaction
            DB::transaction(function () use ($userId, $billId, $notificationType, $title, $message) {
                $notification = notification::create([
                    'bill_id' => $billId,
                    'notification_type_id' => $notificationType->id,
                    'title' => $title,
                    'message' => $message,
                ]);

                notification_read::create([
                    'notification_id' => $notification->id,
                    'user_id' => $userId,
                ]);
            });
        } catch (\Throwable $th) {
            Log::error('Error creating notification: ' . $th->getMessage());
            throw $th;
        }
    }
}
