<?php
/**
 * コンビニエンスストア、入金通知ジョブ
 */
namespace Kaoken\VeritransJpAirWeb\Jobs;


use Carbon\Carbon;
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
    protected $items;


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
            $class = AirWeb::getCvsPaymentNotificationClass();
            $obj = new $class();
            $obj->push_time     = Carbon::createFromFormat('YmdHis', $this->items['pushTime']);
            $obj->push_id       = $this->items['pushId'];
            $obj->order_id      = $this->items['orderId'];
            $obj->csv_type      = $this->items['cvsType'];
            $obj->receipt_no    = $this->items['receiptNo'];
            $obj->receipt_date  = $this->items['receiptDate'];
            $obj->rcv_amount    = $this->items['rcvAmount'];
            $obj->dummy         = $this->items['dummy'];
            event(new CVSPaymentReceivedNotificationEvent($obj));
            $obj->save();
        });
    }

    /**
     * 失敗したジョブの処理
     * @param \Exception $exception
     */
    public function failed(\Exception $exception)
    {
        $a['item'] = $this->items;
        $a['error']['msg'] = $e->getMessage();
        $a['error']['code'] = $e->getCode();
        $a['error']['file'] = $e->getFile();
        $a['error']['line'] = $e->getLine();
        $a['error']['trace'] = $e->getTrace();
        Log::error("Veritrans Jp コンビニ入金通知",$a);
    }
}