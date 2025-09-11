<?php

namespace App\Jobs;

use App\Dto\NotificationDto;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class RetrievePayoutJob implements ShouldQueue
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
            $status = $this->payload['status'] ?? null;
            $metadata = $this->payload['metadata'] ?? [];

            $userId = $metadata['user_id'] ?? ($this->payload['user_id'] ?? null);
            $billId = $metadata['bill_id'] ?? ($this->payload['bill_id'] ?? null);

            if (!$userId || !$billId) {
                Log::error('RetrievePayoutJob: user_id or bill_id missing from payload/metadata.', $this->payload);
                return;
            }

            $notificationMap = [
                'ACCEPTED' => [
                    'type' => 'Payout_Accepted',
                    'title' => 'Payout Diterima',
                    'message' => 'Pembayaran Anda telah diterima dan akan segera dikirimkan ke tujuan.',
                    'description' => 'A payout has been accepted by Xendit for processing.',
                ],
                'SUCCEEDED' => [
                    'type' => 'Payout_Succeeded',
                    'title' => 'Payout Berhasil',
                    'message' => 'Dana Anda telah berhasil dikirim ke tujuan.',
                    'description' => 'A payout has succeeded.',
                ],
                'FAILED' => [
                    'type' => 'Payout_Failed',
                    'title' => 'Payout Gagal',
                    'message' => 'Payout gagal diproses. Silakan coba lagi atau hubungi dukungan.',
                    'description' => 'A payout has failed.',
                ],
                'COMPLIANCE_REJECTED' => [
                    'type' => 'Payout_Rejected',
                    'title' => 'Payout Ditolak',
                    'message' => 'Payout ditolak karena masalah kepatuhan. Silakan hubungi dukungan.',
                    'description' => 'A payout has been rejected due to compliance issues.',
                ],
                'REQUESTED' => [
                    'type' => 'Payout_Requested',
                    'title' => 'Payout Diminta',
                    'message' => 'Payout telah diminta dan sedang dalam proses.',
                    'description' => 'A payout has been requested.',
                ],
                'CANCELLED' => [
                    'type' => 'Payout_Cancelled',
                    'title' => 'Payout Dibatalkan',
                    'message' => 'Payout telah dibatalkan. Silakan hubungi dukungan.',
                    'description' => 'A payout has been cancelled.',
                ],
            ];

            if (array_key_exists($status, $notificationMap)) {
                $data = $notificationMap[$status];

                $notificationService->createNotification(
                    new NotificationDto(
                        userId: $userId,
                        billId: $billId,
                        title: $data['title'],
                        message: $data['message'],
                        type: $data['type'],
                        description: $data['description']
                    )
                );
                Log::info("Notification dispatched for payout status: {$status} for bill ID: {$billId}");
            } else {
                Log::warning("Unhandled payout status: {$status}. No notification created.", $this->payload);
            }

        } catch (\Throwable $th) {
            Log::error("Error processing payout for bill ID {$this->bill->id}: " . $th->getMessage());
        }
    }

    private function createReferenceId(): string
    {
        return 'ref-' . uniqid();
    }
}
