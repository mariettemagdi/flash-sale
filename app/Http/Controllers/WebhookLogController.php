<?php

namespace App\Http\Controllers;

use App\Models\WebhookLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;


class WebhookLogController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'idempotency_key' => 'required|string',
            'order_id' => 'required|integer',
            'status' => 'required|in:success,failure',
        ]);

        $idempotencyKey = $validated['idempotency_key'];
        $orderId = $validated['order_id'];
        $paymentStatus = $validated['status'];
        
        try {
            $existingLog = WebhookLog::where('idempotency_key', $idempotencyKey)->first();
            if($existingLog){
            Log::info('Duplicate webhook received', [
                'idempotency_key' => $idempotencyKey,
                'order_id' => $orderId,
                ]);
                
            return response()->json([
                'status' => 'already_processed',
                'processed_at' => $existingLog->processed_at,], 200);
            }
            $result = DB::transaction(function () use ($idempotencyKey, $orderId, $paymentStatus, $request) {
                //prevents race condition
                $log = WebhookLog::create([
                    'idempotency_key' => $idempotencyKey,
                    'order_id' => $orderId,
                    'status' => $paymentStatus,
                    'payload' => $request->all(),
                    'processed_at' => now(),
                ]);

                $order = Order::find($orderId);
                
                if (!$order) {
                    Log::warning('Webhook received for non-existent order', [
                        'order_id' => $orderId,
                        'idempotency_key' => $idempotencyKey,
                    ]);
                    
                    return ['status' => 'order_not_found'];
                }
            $order = Order::where('id', $orderId)->lockForUpdate()->first();

                if($payementStatus==="success"){
                    $order->update(['status'=>'paid']);
                    Log::info('Payement successful',[
                        'order_id'=>$orderId,
                        'idempotency_key'=> $idempotencyKey,
                    ]);

                }else{
                    $order->update(['status' => 'cancelled']);
                    $order->product->increment('stock', $order->quantity);
                    Cache::forget("product:{$order->product_id}");

                    Log::info('Payment failed, stock restored', [
                        'order_id' => $orderId,
                        'quantity_restored' => $order->quantity,
                        'idempotency_key' => $idempotencyKey,
                    ]);
                }
            return ['status' => 'processed', 'order_status' => $order->status];
        });
        return response()->json($result, 200);
        }catch(\Exception $e){
            Log::error('Webhook processing failed', [
                'idempotency_key' => $idempotencyKey,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Processing failed'], 500);
        }

    }

}
