<?php
namespace App\Services;

use App\Models\bills;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Xendit\Configuration;
use Xendit\Invoice\InvoiceApi;

class XenditService
{
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

    public static function updateExpiredInvoice($billId, $invoiceId)
    {
        $invoice = self::getInvoice($invoiceId);
        if ($invoice['status'] === 'EXPIRED') {
            $bill = bills::findOrFail($billId);

            DB::transaction(function () use ($bill, $invoice) {
                $bill->update([
                    'status' => 'overdue',
                    'updated_at' => Carbon::parse($invoice['expiry_date']),
                ]);
            });
        }
    }

    public static function checkUnpaidBills()
    {
        $unpaidOrders = bills::where('status', 'pending')->get();

        foreach ($unpaidOrders as $order) {
            self::updateExpiredInvoice($order->id, $order->transaction_id);
        }
    }
}
