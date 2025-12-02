<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;


class ProductController extends Controller
{
    /**
     * Display the specified resource.
     */
    public function show(string $id):JsonResponse
    {
        //take id instead of prodcut to avoid bypassing cache
        //current time in microseconds
        $startTime=microtime(true);

        // after 60 sec it expires
        $product = Cache::remember("product:{$id}",
        60,
        fn()=>Product::find($id));

        if(!$product){
            return response()->json(['error'=>'Product not Found'],404); 
        }

        //*1000 converts from seconds to milliseconds
        $duration=(mocrotime(true)-$startTime)*1000;

        Log::info('Product fetched',[
            'product_id'=>$id,
            'duration_ms'=>round($duration,2),
            'cached'=>Cache::has("product:{$id}"),
        ]);


        return response()->json([
            'id'=>$product->id,
            'name'=>$product->name,
            'ptice'=>$product->price,
            'available_stock' =>$product->stock,
        ]);

    }


}
