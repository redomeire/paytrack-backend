<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\bills;
use App\Models\payments;
use App\Dto\NotificationDto;
use App\Mail\PaymentFailed;
use App\Mail\PaymentSuccess;
use App\Jobs\ProcessPayoutJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Services\NotificationService;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class RetrieveCheckoutJob implements ShouldQueue
{
    use Queueable;

    protected array $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function handle(NotificationService $notificationService): void
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

            if (!$bill) {
                Log::warning("Tagihan dengan ID '{$invoiceId}' tidak ditemukan.");
                return;
            }

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
                    ProcessPayoutJob::dispatch($this->payload, $bill)->onQueue('payouts');

                    $user = User::find($bill->user_id);
                    Mail::to($user->email)->send(new PaymentSuccess($bill));

                    $notificationService->createNotification(
                        $bill->user_id,
                        $bill->id,
                        'Pembayaran Berhasil',
                        "Pembayaran untuk tagihan '{$bill->name}' sebesar {$bill->amount} telah berhasil.",
                        'Payment_Status_Success',
                        'Notifikasi ini dikirimkan ketika pembayaran tagihan berhasil.'
                    );
                }
            } elseif ($status === 'EXPIRED') {
                Log::warning("Tagihan #{$bill->id} kedaluwarsa.");
                $bill->status = 'overdue';
                $bill->save();
                // create payment
                payments::create([
                    'bill_id' => $bill->id,
                    'amount' => $bill->amount,
                    'currency' => $bill->currency,
                    'due_date' => $bill->due_date ?? now(),
                    'notes' => 'Payment expired via Xendit Webhook',
                ]);

                $notificationsService->createNotification(
                    $bill->user_id,
                    $bill->id,
                    'Tagihan Kedaluwarsa',
                    "Tagihan '{$bill->name}' telah kedaluwarsa dan belum dibayar.",
                    'Payment_Status_Expired',
                    'Notifikasi ini dikirimkan ketika tagihan telah kedaluwarsa tanpa pembayaran.'
                );
            } elseif ($status === 'FAILED') {
                Log::warning("Tagihan #{$bill->id} gagal.");
                $bill->status = 'failed';
                $bill->save();

                // create payment
                payments::create([
                    'bill_id' => $bill->id,
                    'amount' => $bill->amount,
                    'currency' => $bill->currency,
                    'due_date' => $bill->due_date ?? now(),
                    'notes' => 'Payment failed via Xendit Webhook',
                ]);


                if ($bill->user_id) {
                    $user = User::find($bill->user_id);
                    Mail::to($user->email)->send(new PaymentFailed($bill));
                }

                $notificationService->createNotification(
                    $bill->user_id,
                    $bill->id,
                    'Pembayaran Gagal',
                    "Pembayaran untuk tagihan '{$bill->name}' gagal. Silakan coba lagi.",
                    'Payment_Status_Failed',
                    'Notifikasi ini dikirimkan ketika pembayaran tagihan gagal.'
                );
            }
        } catch (\Throwable $th) {
            Log::error('Error processing Xendit webhook: ' . $th->getMessage(), $this->payload);
            throw $th;
        }
    }
}
