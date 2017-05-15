<?php
/**
 * コンビニ決済で、入金期日が過ぎた処理をするジョブ
 */
namespace Kaoken\VeritransJpAirWeb\Jobs;


use Illuminate\Database\Eloquent\Model;
use Kaoken\VeritransJpAirWeb\Events\CVSPaymentReceivedNotificationEvent;
use Kaoken\VeritransJpAirWeb\VeritransJpAirWebCVSPaymentNotification;

use AirWeb;
use DB;
use Log;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\Request;

class CVSDueDateHasPassedJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 最大試行回数
     *
     * @var int
     */
    public $tries = 3;

    /**
     * @var VeritransJpAirWebPayment
     */
    public $obj = null;

    /**
     * Create a new job instance.
     *
     * @param VeritransJpAirWebPayment    $obj
     */
    public function __construct( $obj )
    {
        $this->obj = $obj;
    }


    /**
     * ジョブの実行
     */
    public function handle()
    {
//        Log::info("Veritrans Jp コンビニ決済で、入金期日が過ぎた");
        DB::transaction(function() {
            event(new CVSDueDateHasPassed($obj));
        });

//        Log::info("Veritrans Jp コンビニ決済で、入金期日が過ぎた終了");
    }
}