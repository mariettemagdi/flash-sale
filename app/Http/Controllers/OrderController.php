<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;


class OrderController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request):JsonResponse
    {
        //
        $validated=$request->validate([
            'hold_id'=>'required|integer|exists:holds,id',
        ]);

        try{
            $order=DB::transaction(function () use($validated){
                $hold=Hold::where('id',$validated['hold_id'])->LockForUpdate()->first();

                if($hold->status !=='active'){
                    throw ValidationException::withMessages([
                        'hold_id'=>['This hold has already been used or expired']
                    ]);
                }

                if($hold->isExpired()){
                    throw ValidationException::withMessages([
                        'hold_id' => ['This hold has expired']
                    ]);
                }

                $hold->update(['status'=>'used']);

                //create order
                $product=$hold->product;
                $order=Order::create([
                    'hold_id' => $hold->id,
                    'product_id' => $product->id,
                    'quantity' => $hold->quantity,
                    'total_price' => $product->price * $hold->quantity,
                    'status' => 'pending',
                ]);

                return $order;//return from from the transaction callback
            });

            Log::info('Order created',[
                'order_id' => $order->id,
                'hold_id' => $validated['hold_id'],
                'total_price' => $order->total_price,
            ]);

            return response()->json([
                'order_id' => $order->id,
                'hold_id' => $order->hold_id,
                'product_id' => $order->product_id,
                'quantity' => $order->quantity,
                'total_price' => $order->total_price,
                'status' => $order->status,
            ], 201);


        }catch(ValidationException $e){
            return response()->json(['errors'=>$e->errors()],422);
        }
    }
}
