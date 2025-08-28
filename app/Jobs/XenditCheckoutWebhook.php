<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\bills;
use App\Models\payments;
use App\Mail\PaymentFailed;
use App\Mail\PaymentSuccess;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

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
                    }

                } elseif ($status === 'EXPIRED') {
                    $bill->status = 'overdue';
                    $bill->save();
                    Log::warning("Tagihan #{$bill->id} kedaluwarsa.");

                } elseif ($status === 'FAILED') {
                    $bill->status = 'failed';
                    $bill->save();

                    Log::warning("Tagihan #{$bill->id} gagal.");

                    if ($bill->user_id) {
                        $user = User::find($bill->user_id);
                        Mail::to($user->email)->send(new PaymentFailed($bill));
                    }
                }
            } else {
                Log::warning("Tagihan dengan ID '{$invoiceId}' tidak ditemukan.");
            }
        } catch (\Throwable $th) {
            Log::error('Error processing Xendit webhook: ' . $th->getMessage(), $this->payload);
            throw $th;
        }
    }
}
