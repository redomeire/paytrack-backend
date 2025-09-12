<?php

namespace App\Jobs;

use App\Dto\NotificationDto;
use App\Models\bills;
use App\Services\NotificationService;
use App\Services\XenditService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Xendit\Payout\CreatePayoutRequest;

class ProcessPayoutJob implements ShouldQueue
{
    use Queueable;
    protected array $payload;
    protected bills $bill;
    /**
     * Create a new job instance.
     */
    public function __construct(array $payload, bills $bill)
    {
        $this->payload = $payload;
        $this->bill = $bill;
    }

    /**
     * Execute the job.
     */
    public function handle(
        NotificationService $notificationService,
        XenditService $xenditService
    ): void {
        try {
            Log::info('Payout request : ', (array) $this->payload);

            $result = $xenditService->createPayout($this->payload, $this->bill);
            Log::info('Payout result : ', (array) $result);

            if (!$result) {
                Log::error("Payout creation failed for bill ID {$this->bill->id}");
                return;
            }

            $notificationService->createNotification(
                new NotificationDto(
                    userId: $this->bill->user_id,
                    billId: $this->bill->id,
                    type: 'Payout_Initiated',
                    title: 'Payout Initiated',
                    message: "Payout of {$this->payload['amount']} {$this->payload['currency']} initiated to {$this->payload['payment_destination']} via {$this->payload['payment_channel']}.",
                    description: 'A payout has been initiated.'
                ));
        } catch (\Throwable $th) {
            Log::error("Error processing payout for bill ID {$this->bill->id}: " . $th->getMessage());
        }
    }
}
