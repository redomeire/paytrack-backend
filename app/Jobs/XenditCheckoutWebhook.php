<?php

namespace App\Jobs;

use App\Models\bills;
use App\Models\payments;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class XenditCheckoutWebhook implements ShouldQueue
{
    use Queueable;
    protected array $payload;
    /**
     * Create a new job instance.
     */
    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Webhook Xendit diterima. Payload:', $this->payload);

        // Ambil status transaksi dari payload
        $status = $this->payload['status'] ?? null;
        $invoiceId = $this->payload['external_id'] ?? null;

        if (!$status || !$invoiceId) {
            Log::error('Payload webhook tidak valid.', $this->payload);
            return;
        }

        // Cari bill berdasarkan invoice_id
        $bill = bills::where('id', $invoiceId)->first();

        if ($bill) {
            // Perbarui status pesanan berdasarkan status dari webhook
            // membuat payment
            $payment = payments::create([
                'bill_id' => $bill->id,
                'amount' => $this->payload['amount'] ?? 0,
                'currency' => $this->payload['currency'] ?? 'IDR',
                'paid_date' => $status === 'PAID' ? now() : null,
                'due_date' => $bill->due_date,
                'payment_method' => $this->payload['payment_method'] ?? 'Xendit',
                'payment_reference' => $this->payload['id'] ?? null,
                'notes' => 'Payment via Xendit Webhook',
            ]);
            if ($status === 'PAID') {
                $bill->update(['status' => 'paid']);
                Log::info("Pesanan #{$bill->id} berhasil diperbarui menjadi 'paid'.");

                Mail::to($order->user->email)->send(new \App\Mail\PaymentSuccess($bill));
            } elseif ($status === 'FAILED') {
                Mail::to($order->user->email)->send(new \App\Mail\PaymentFailed($bill));
                $bill->update(['status' => 'failed']);
                Log::warning("Pesanan #{$bill->id} gagal");
            } elseif ($status === 'EXPIRED') {
                $bill->update(['status' => 'overdue']);
                Log::warning("Pesanan #{$bill->id} kedaluwarsa.");
            }
        } else {
            Log::warning("Pesanan dengan invoice ID '{$invoiceId}' tidak ditemukan.");
        }
    }
}
