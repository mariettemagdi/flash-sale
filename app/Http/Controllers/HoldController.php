<?php

namespace App\Http\Controllers;

use App\Models\Hold;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;

class HoldController extends Controller
{
 
   //POST /api/holds
    public function store(Request $request):JsonResponse
    {
        $validated=$request->validate([
          'product_id'=>'required|exists:products,id',
          'quantity'=>'required|integer|min:1',
        ]);

        $startTime=microtime(true);
        $retries=0;
        $maxRetries=3;

        while($retries<$maxRetries){
            try{
               $hold=DB::transaction(function () use ($validated){
               $product=Product::where('id',$validated['product_id'])->lockForUpdate()->first();

               if($product->stock < $validated['quantity']){
                    throw ValidationException::withMessages(['quantity' => ['Not enough stock available. Available: ' . $product->stock]]);
               }

               $product->decrement('stock',$validated['quantity']);

               $hold=Hold::create([
                'product_id' => $product->id,
                'quantity' => $validated['quantity'],
                'expires_at' => now()->addMinutes(2),
                'status' => 'active',
               ]);

               Cache::forget("product:{$product->id}");
               return $hold;
            },5);//5 trials for deadlock handling

            $duration=(microtime(true)-$startTime)*1000;

            Log::info('Hold created successfully',[
                    'hold_id' => $hold->id,
                    'product_id' => $validated['product_id'],
                    'quantity' => $validated['quantity'],
                    'retries' => $retries,
                    'duration_ms' => round($duration, 2),
            ]);

            return response()->json([
                'hold_id' => $hold->id,
                'product_id' => $hold->product_id,
                'quantity' => $hold->quantity,
                'expires_at' => $hold->expires_at->toIso8601String(),

            ],201);

            }catch (\Illuminate\Database\QueryException $e){

                if($e->getCode()==='4001' || str_contains($e->getMessage(),'Deadlock')){
                    $retries++;

                    Log::warning('Deadlock detected, retrying', [
                        'attempt' => $retries,
                        'product_id' => $validated['product_id'],
                    ]);
                
                    if($retries >= $maxRetries){
                        Log::error('Max retries reached for hold creation', [
                                'product_id' => $validated['product_id'],
                            ]);
                            return response()->json([
                                'error' => 'System busy, please try again'
                            ], 503);
                    }
                        usleep(100000 * $retries); // Exponential backoff
                continue;
                }
            throw $e;

            } catch (\Illuminate\Validation\ValidationException $e) {
                return response()->json(['errors' => $e->errors()], 422);
            }
        }
        return response()->json(['error' => 'Failed to create hold'],500);


    }

}