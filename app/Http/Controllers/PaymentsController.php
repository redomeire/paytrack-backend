<?php

namespace App\Http\Controllers;

use App\Models\bills;
use App\Models\payments;
use Xendit\Configuration;
use Illuminate\Http\Request;
use Xendit\Invoice\InvoiceApi;
use Illuminate\Support\Facades\DB;
use App\Jobs\XenditCheckoutWebhook;
use App\Http\Controllers\BaseController;
use Xendit\Invoice\CreateInvoiceRequest;
use Illuminate\Support\Facades\Validator;

class PaymentsController extends BaseController
{
    private $xenditInvoiceApi;
    public function __construct()
    {
        Configuration::setXenditKey(config('services.xendit.secret_key'));
        $this->xenditInvoiceApi = new InvoiceApi();
    }
    public function index(Request $request)
    {
        try {
            $userId = $request->user()->id;
            $search = $request->query('search', '');
            $payments = DB::table('payments')
                ->join('bills', 'payments.bill_id', '=', 'bills.id')
                ->join('users', 'bills.user_id', '=', 'users.id')
                ->where('users.id', $userId)
                ->where('name', 'like', '%' . $search . '%')
                ->when($request->has('start_date') && $request->has('end_date'), function ($query) use ($request) {
                    $query->whereBetween('payments.due_date', [
                        $request->query('start_date'),
                        $request->query('end_date'),
                    ]);
                })
                ->orderBy('paid_date', 'desc')
                ->select(
                    'payments.*',
                    'bills.*',
                )
                ->paginate(10);
            return $this->sendResponse($payments, 'Payments retrieved successfully.');
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), null, 500);
        }
    }
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'bill_id' => 'required|uuid|exists:bills,id',
                'amount' => 'required|numeric',
                'currency' => 'required|string|in:IDR,USD',
                'paid_date' => 'nullable|date',
                'due_date' => 'required|date|after_or_equal:paid_date',
                'payment_method' => 'nullable|string|max:50',
                'payment_reference' => 'nullable|string|max:100',
                'notes' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors());
            }

            try {
                $payment = payments::create($request->all());
                return $this->sendResponse($payment, 'Payment created successfully.', 201);
            } catch (\Throwable $th) {
                return $this->sendError($th->getMessage());
            }
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), null, 500);
        }
    }
    public function detail(Request $request, $id)
    {
        try {
            $userId = $request->user()->id;
            $payment = payments::find($id);
            if (!$payment) {
                return $this->sendError('Payment not found', null, 404);
            }
            return $this->sendResponse($payment, 'Payment retrieved successfully.');
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), null, 500);
        }
    }
    public function update(Request $request, $id)
    {
        try {
            $userId = $request->user()->id;
            $validator = Validator::make($request->all(), [
                'bill_id' => 'sometimes|uuid|exists:bills,id',
                'amount' => 'sometimes|numeric',
                'currency' => 'sometimes|string|in:IDR,USD',
                'paid_date' => 'sometimes|date',
                'due_date' => 'sometimes|date|after_or_equal:paid_date',
                'payment_method' => 'sometimes|string|max:50',
                'payment_reference' => 'sometimes|string|max:100',
                'notes' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors());
            }

            $payment = payments::where('id', $id)
                ->whereHas('bill', function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                })
                ->first();
            if (!$payment) {
                return $this->sendError('Payment not found', null, 404);
            }
            $payment->update($request->all());
            return $this->sendResponse($payment, 'Payment updated successfully.');
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage());
        }
    }
    public function delete(Request $request, $id)
    {
        try {
            $userId = $request->user()->id;
            $payment = payments::where('id', $id)
                ->whereHas('bill', function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                })
                ->first();
            if (!$payment) {
                return $this->sendError('Payment not found', null, 404);
            }
            $payment->delete();
            return $this->sendResponse(null, 'Payment deleted successfully.');
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), null, 500);
        }
    }
    public function checkout(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'billId' => 'required|exists:bills,id',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors());
        }

        try {
            $user = $request->user();
            $bill = bills::where('id', $request->billId)
                ->where('user_id', $user->id)
                ->first();
            if (!$bill) {
                return $this->sendError('Bill not found', null, 404);
            }
            $invoiceRequest = new CreateInvoiceRequest([
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
            ]);
            
            $invoice = $this->xenditInvoiceApi->createInvoice($invoiceRequest);
            return $this->sendResponse(['invoice_url' => $invoice->getInvoiceUrl()], 'Checkout URL generated successfully.', 201);

        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), null, 500);
        }
    }
    public function webhook(Request $request)
    {
        try {
            XenditCheckoutWebhook::dispatch($request->all());
            return $this->sendResponse(null, 'Checkout process started.', 202);
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), null, 500);
        }
    }
}
