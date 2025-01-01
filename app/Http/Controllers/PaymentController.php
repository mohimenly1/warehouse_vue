<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Voucher;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentController extends Controller
{

    public function index()
{
    $subscriptions = Subscription::with('user')->get();
    return response()->json(['subscriptions' => $subscriptions]);
}

    // public function store(Request $request)
    // {
    //     $validated = $request->validate([
    //         'user_id' => 'required|exists:users,id',
    //         'subscription_type' => 'required|in:normal,trader',
    //         'payment_method' => 'required|in:credit_card,voucher,bank_transfer',
    //         'voucher_code' => 'nullable:payment_method,voucher|string',
    //         'warehouse_name' => 'required|string|max:255',
    //         'warehouse_location' => 'required|string|max:255',
    //     ]);
    
    //     try {
    //         $subscriptionAmount = $this->getSubscriptionAmount($validated['subscription_type']);
    //         if (!$subscriptionAmount) {
    //             return response()->json(['message' => 'Invalid subscription type.'], 400);
    //         }
    
    //         if ($validated['payment_method'] === 'voucher' && !$this->validateVoucher($validated['voucher_code'])) {
    //             return response()->json(['message' => 'Invalid voucher code.'], 400);
    //         }
    
    //         $subscription = null;
    //         $warehouse = null;
    
    //         DB::transaction(function () use ($validated, $subscriptionAmount, &$subscription, &$warehouse) {
    //             $subscription = Subscription::create([
    //                 'user_id' => $validated['user_id'],
    //                 'subscription_type' => $validated['subscription_type'],
    //                 'amount' => $subscriptionAmount,
    //                 'payment_method' => $validated['payment_method'],
    //                 'voucher_code' => $validated['voucher_code'],
    //                 'paid_at' => now(),
    //                 'is_paid' => true,
    //             ]);
    
    //             $warehouse = Warehouse::create([
    //                 'name' => $validated['warehouse_name'],
    //                 'location' => $validated['warehouse_location'],
    //                 'user_id' => $validated['user_id'],
    //             ]);
    //         });
    
    //         return response()->json([
    //             'message' => 'Subscription and warehouse created successfully',
    //             'subscription' => $subscription,
    //             'warehouse' => $warehouse,
    //         ]);
    //     } catch (\Exception $e) {
    //         Log::error('Error storing subscription:', ['error' => $e->getMessage()]);
    //         return response()->json(['message' => 'An error occurred. Please try again later.'], 500);
    //     }
    // }
    



    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'user_id' => 'required|exists:users,id',
            'subscription_type' => 'required|string',
            'payment_method' => 'required|string',
            'voucher_code' => 'nullable|string',
            'warehouse_name' => 'required|string|max:255',
            'warehouse_location' => 'required|string|max:255',
        ]);
    
        // If payment method is 'voucher', validate the voucher code
        if ($validatedData['payment_method'] === 'voucher') {
            $voucher = Voucher::where('code', $validatedData['voucher_code'])->first();
    
            // Check if voucher exists and is not used
            if (!$voucher) {
                return response()->json(['message' => 'Invalid voucher code.'], 400);
            }
    
            if ($voucher->is_used) {
                return response()->json(['message' => 'This voucher has already been used.'], 400);
            }
    
            // Mark the voucher as used
            $voucher->is_used = true;
            $voucher->save();
    
            // Use the voucher amount as the subscription amount
            $validatedData['amount'] = $voucher->amount;
            $validatedData['is_paid'] = true; // Consider the payment as completed
        } else {
            $validatedData['amount'] = $request->input('amount', 0); // Default amount for non-voucher payments
            $validatedData['is_paid'] = false; // Non-voucher payments require further processing
        }
    
        DB::beginTransaction();
        
        try {
            // Create the subscription
            $subscription = Subscription::create([
                'user_id' => $validatedData['user_id'],
                'subscription_type' => $validatedData['subscription_type'],
                'amount' => $validatedData['amount'],
                'payment_method' => $validatedData['payment_method'],
                'voucher_code' => $validatedData['voucher_code'] ?? null,
                'paid_at' => now(),
                'is_paid' => $validatedData['is_paid'],
            ]);
    
            // Create the warehouse
            $warehouse = Warehouse::create([
                'name' => $validatedData['warehouse_name'],
                'location' => $validatedData['warehouse_location'],
                'user_id' => $validatedData['user_id'],
            ]);
    
            DB::commit();
    
            return response()->json([
                'message' => 'Subscription and warehouse created successfully.',
                'subscription' => $subscription,
                'warehouse' => $warehouse,
                'voucher_amount' => $validatedData['amount'],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'An error occurred. Please try again later.'], 500);
        }
    }
    


    private function getSubscriptionAmount($subscriptionType)
    {
        $pricing = [
            'normal' => 50.00,
            'trader' => 100.00,
        ];

        return $pricing[$subscriptionType] ?? null;
    }

    public function validateVoucher($code)
    {
        $voucher = Voucher::where('code', $code)->where('is_used', false)->first();
        if ($voucher) {
            return response()->json(['valid' => true]);
        }
        return response()->json(['valid' => false], 400);
    }


    public function generateVoucher(Request $request)
    {
        $request->validate([
            'code' => 'required_without:quantity|string|unique:vouchers',
            'quantity' => 'required_without:code|integer|min:1',
            'amount' => 'required|numeric|min:0',
            'user_id' => 'required|exists:users,id',
        ]);
    
        if ($request->has('quantity')) {
            $vouchers = [];
            for ($i = 0; $i < $request->quantity; $i++) {
                $vouchers[] = Voucher::create([
                    'code' => str::random(10),
                    'created_by' => $request->user_id,
                    'amount' => $request->amount,
                ]);
            }
            return response()->json(['message' => 'Vouchers generated successfully', 'vouchers' => $vouchers]);
        } else {
            $voucher = Voucher::create([
                'code' => $request->code,
                'created_by' => $request->user_id,
                'amount' => $request->amount,
            ]);
            return response()->json(['message' => 'Voucher created successfully', 'voucher' => $voucher]);
        }
    }
    
    

    public function getVouchers()
    {
        $vouchers = Voucher::with('creator')->get();
        return response()->json(['vouchers' => $vouchers]);
    }



    public function getProfitsData()
    {
        $totalAmount = Subscription::where('is_paid', true)->sum('amount'); // Sum of all paid subscriptions' amounts
        $subscriptionsByType = Subscription::where('is_paid', true)
            ->selectRaw('subscription_type, count(*) as count')
            ->groupBy('subscription_type')
            ->get();
        $subscriptionsByPaymentMethod = Subscription::where('is_paid', true)
            ->selectRaw('payment_method, count(*) as count')
            ->groupBy('payment_method')
            ->get();

        return response()->json([
            'total_amount' => $totalAmount,
            'subscriptions_by_type' => $subscriptionsByType,
            'subscriptions_by_payment_method' => $subscriptionsByPaymentMethod
        ]);
    }
}