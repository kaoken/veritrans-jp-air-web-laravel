<?php
/**
 * 決済完了通知ジョブ
 */
namespace Kaoken\VeritransJpAirWeb\Jobs;

use AirWeb;
use Carbon\Carbon;
use Log;
use DB;
use Kaoken\VeritransJpAirWeb\Events\PaymentNotificationEvent;
use Kaoken\VeritransJpAirWeb\VeritransJpAirWebCVSPaymentNotification;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\Request;


class PaymentNotificationJob implements ShouldQueue
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
     * `orderId`,`mStatus`,`vResultCode`,`mErrMsg`,`merchantEncryptionKey`
     * @var array
     */
    public $items;


    /**
     * Create a new job instance.
     *
     * @param array    $items 決済完了通知で送られてきた内容
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
//        Log::info("Veritrans Jp 決済完了通知");
        DB::transaction(function() {

            $class = AirWeb::getPaymentNotificationClass();
            $obj = new $class();

            $obj->order_id      = $this->items['orderId'];
            $obj->status        = $this->items['mStatus'];
            $obj->result_code   = $this->items['vResultCode'];
            $obj->err_msg       = $this->items['mErrMsg'];
            $obj->merchant_encryption_key = $this->items['merchantEncryptionKey'];
            $obj->created_at    = Carbon::now();

            // リスナー側で、カードの支払いが成功していた場合、
            // `VeritransJpAirWebPayment` の `paid_at` で支払い済み確定判断をすること
            event(new PaymentNotificationEvent($obj));
            $obj->save();
        });

//        Log::info("Veritrans Jp 決済完了通知 正常終了");
    }
}