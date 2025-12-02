<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Hold;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;


class ReleaseExpiredHolds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'holds:release-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Release expired holds and return stock to products';

    /**
     * Execute the console command.
     */
    public function handle():int
    {
        $startTime=microtime(true);
        $expiredHolds=Hold::where('status','active')->where('expires_at','<',now())->get();
        $releasedCount=0;
        $totalQuantityReleased=0;

        foreach($expiredHolds as $hold){
            try{
                DB::transaction(function () use($hold,&$totalQuantityReleased){
                    $hold->update(['status'=>'expired']);
                    $hold->product->increment('stock',$hold->quantity);

                    Cache::forget("product:{$hold->product_id}");
                    $totalQuantityReleased+=$hold->quantity;
                } );
                $releaseCount++;
                Log::info('Hold expired and released', [
                    'hold_id' => $hold->id,
                    'product_id' => $hold->product_id,
                    'quantity_released' => $hold->quantity,
                ]);


            }catch(\Exception $e){
                Log::error('Failed to release expired hold', [
                    'hold_id' => $hold->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $duration=(microtime(true)-$startTime)*1000;
        
        Log::info('Expired holds release completed', [
            'released_count' => $releasedCount,
            'total_quantity_released' => $totalQuantityReleased,
            'duration_ms' => round($duration, 2),
        ]);

        $this->info("Released {$releasedCount} expired holds ({$totalQuantityReleased} items)");

        return self::SUCCESS;

    }
}
