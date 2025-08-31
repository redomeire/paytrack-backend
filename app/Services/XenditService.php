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

    public static function updateExpiredInvoice($billId)
    {
        $invoice = self::getInvoice($invoiceId);
        if ($invoice['status'] === 'EXPIRED') {
            $bill = bills::findOrFail($billId);

            DB::transaction(function () use ($bill, $invoice) {
                $bill->update([
                    'status' => 'overdue'
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
}
