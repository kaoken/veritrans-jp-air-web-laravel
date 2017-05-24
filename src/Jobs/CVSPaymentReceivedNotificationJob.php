<?php
/**
 * コンビニエンスストア、入金通知ジョブ
 */
namespace Kaoken\VeritransJpAirWeb\Jobs;


use Carbon\Carbon;
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


class CVSPaymentReceivedNotificationJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;


    /**
     * 最大試行回数
     *
     * @var int
     */
    public $tries = 3;


    /**
     * 決済完了通知で送られてきた内容
     * `numberOfNotify`,`pushTime`,`pushId`,
     * `orderId`,`cvsType`,`receiptNo`,`receiptDate`,`rcvAmount`,`dummy`
     * @var array
     */
    public $items;


    /**
     * Create a new job instance.
     *
     * @param array    $items コンビニ入金通知で送られてきた内容(Validatorなどでチェック済み)
     */
    public function __construct( array $items )
    {
        $this->items = $items;
    }

    /**
     * ジョブの実行
     */
    public function handle()
    {
//        Log::info("Veritrans Jp コンビニ入金通知");
        DB::transaction(function() {
            $class = AirWeb::getCVSPaymentNotificationClass();
            // 既に通知済みの場合は、無視する
            $c = $class::where('order_id')->count();
            if( $c > 0 ){
                Log::warning('既に存在するコンビニ入金通知です。',['post'=>$this->items]);
                return;
            }

            $obj = new $class();
            $obj->push_time     = Carbon::createFromFormat('YmdHis', $this->items['pushTime']);
            $obj->push_id       = $this->items['pushId'];
            $obj->order_id      = $this->items['orderId'];
            $obj->csv_type      = $this->items['cvsType'];
            $obj->receipt_no    = $this->items['receiptNo'];
            $obj->receipt_date  = Carbon::createFromFormat('YmdHis', $this->items['receiptDate']);
            $obj->rcv_amount    = $this->items['rcvAmount'];
            $obj->dummy         = $this->items['dummy'];
            event(new CVSPaymentReceivedNotificationEvent($obj));
            $obj->save();
        });
    }
}