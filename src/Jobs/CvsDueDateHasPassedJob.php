<?php
/**
 * コンビニ決済で、入金期日が過ぎた処理をするジョブ
 */
namespace Kaoken\VeritransJpAirWeb\Jobs;


use Kaoken\VeritransJpAirWeb\Events\CVSPaymentReceivedNotificationEvent;
use Kaoken\VeritransJpAirWeb\VeritransJpAirWebCvsPaymentNotification;

use AirWeb;
use DB;
use Log;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\Request;

class CvsDueDateHasPassedJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 最大試行回数
     *
     * @var int
     */
    public $tries = 3;

    /**
     * @var int
     */
    public $day = 1;

    /**
     * Create a new job instance.
     *
     * @param int    $day 日数
     */
    public function __construct( $day )
    {
        $this->day = $day;
    }


    /**
     * ジョブの実行
     */
    public function handle()
    {
//        Log::info("Veritrans Jp コンビニ決済で、入金期日が過ぎた");
        DB::transaction(function() {
            event(new CVSPaymentReceivedNotificationEvent($obj,$this->ip));
        });

//        Log::info("Veritrans Jp コンビニ決済で、入金期日が過ぎた終了");
    }

    /**
     * 失敗したジョブの処理
     * @param \Exception $exception
     */
    public function failed(\Exception $exception)
    {
        $a['day'] = $this->day;
        $a['error']['msg'] = $e->getMessage();
        $a['error']['code'] = $e->getCode();
        $a['error']['file'] = $e->getFile();
        $a['error']['line'] = $e->getLine();
        $a['error']['trace'] = $e->getTrace();
        Log::error("Veritrans Jp コンビニ決済で、入金期日が過ぎた",$a);
    }
}