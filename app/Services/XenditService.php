<?php
namespace App\Services;

use App\Models\User;
use App\Models\bills;
use Xendit\Configuration;
use Xendit\Payout\PayoutApi;
use Xendit\Invoice\InvoiceApi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Xendit\Payout\CreatePayoutRequest;
use Xendit\Invoice\CreateInvoiceRequest;

class XenditService
{
    protected InvoiceApi $invoiceApi;
    protected PayoutApi $payoutApi;
    public function __construct(InvoiceApi $invoiceApi, PayoutApi $payoutApi)
    {
        $this->invoiceApi = $invoiceApi;
        $this->payoutApi = $payoutApi;
    }
    public static function isHasUnpaidBills($userId = null)
    {
        $unpaidOrders = bills::when($userId, function ($q) use ($userId) {
            return $q->where('user_id', $userId);
        })
            ->where('status', 'pending')
            ->get();
        return $unpaidOrders->count() > 0;
    }

    public static function getInvoice($invoiceId)
    {
        Configuration::setXenditKey(config('services.xendit.secret_key'));
        $xenditInvoiceApi = new InvoiceApi();
        return $xenditInvoiceApi->getInvoiceById($invoiceId);
    }

    public static function updateExpiredInvoice($billId)
    {
        $invoice = self::getInvoice($invoiceId);
        if ($invoice['status'] === 'EXPIRED') {
            $bill = bills::findOrFail($billId);

            DB::transaction(function () use ($bill, $invoice) {
                $bill->update([
                    'status' => 'overdue',
                ]);
            });
        }
    }

    public static function checkUnpaidBills()
    {
        $unpaidBills = bills::where('status', 'pending')->get();

        foreach ($unpaidBills as $unpaidBill) {
            self::updateExpiredInvoice($unpaidBill->id);
        }
    }

    public function createInvoice(bills $bill, User $user)
    {
        try {
            $invoiceRequestPayload = [
                'external_id' => $bill->id,
                'amount' => $bill->amount,
                'description' => "Credit Order #" . $bill->billId,
                'customer' => [
                    'given_names' => $user->first_name . ' ' . $user->last_name,
                    'email' => $user->email,
                ],
                'currency' => 'IDR',
                'invoice_duration' => 3600, // 1 hour
                'success_redirect_url' => '' . config('app.frontend_url') . '/payment/checkout/success',
                'failure_redirect_url' => '' . config('app.frontend_url') . '/payment/checkout/failure?billId=' . $bill->id,
            ];
            $invoiceRequest = new CreateInvoiceRequest($invoiceRequestPayload);
            $invoicePayload = $this->invoiceApi->createInvoice($invoiceRequest);
            return $invoicePayload;
        } catch (\Throwable $th) {
            Log::error("message: " . $th->getMessage() . " file: " . $th->getFile() . " line: " . $th->getLine());
        }
    }

    public function createPayout(array $payload, bills $bill)
    {
        try {
            $payoutRequestPayload = [
                'reference_id' => 'ref-
                ',
                'channel_code' => $payload['payment_channel'],
                'amount' => $payload['amount'],
                'currency' => $payload['currency'],
                'channel_properties' => [
                    'account_number' => $payload['payment_destination'],
                ],
                'metadata' => [
                    'user_id' => $bill->user_id,
                    'bill_id' => $bill->id,
                ],
            ];
            $payoutRequest = new CreatePayoutRequest($payoutRequestPayload);
            $payoutPayload = $this->payoutApi->createPayout($payoutRequest);
            return $payoutPayload;
        } catch (\Throwable $th) {
            Log::error("Payout creation error: " . $th->getMessage());
        }
    }
}
