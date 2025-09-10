<?php

namespace App\Jobs;

use App\Models\bills;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Xendit\Payout\CreatePayoutRequest;
use Xendit\Payout\DigitalPayoutChannelProperties;

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
    public function handle(NotificationService $notificationService): void
    {
        try {
            $channel_properties = new DigitalPayoutChannelProperties([
                'account_number' => $this->payload['payment_destination'],
            ]);
            new CreatePayoutRequest([
                'reference_id' => $this->createReferenceId(),
                'channel_code' => $this->payload['payment_channel'],
                'amount' => $this->payload['amount'],
                'currency' => $this->payload['currency'],
                'channel_properties' => $channel_properties,
                'metadata' => [
                    'user_id' => $this->bill->user_id,
                    'bill_id' => $this->bill->id
                ]
            ]);
            $notificationService->createNotification(
                new NotificationDto(
                    userId: $this->bill->user_id,
                    billId: $this->bill->id,
                    type: 'Payout_Initiated',
                    typeId: 1,
                    title: 'Payout Initiated',
                    message: "Payout of {$this->payload['amount']} {$this->payload['currency']} initiated to {$this->payload['payment_destination']} via {$this->payload['payment_channel']}.",
                    description: 'A payout has been initiated.'
                ));
        } catch (\Throwable $th) {
            Log::error("Error processing payout for bill ID {$this->bill->id}: " . $th->getMessage());
        }
    }

    private function createReferenceId(): string
    {
        return 'ref-' . uniqid();
    }
}
