<?php
/**
 * コンビニ決済で、入金期日が過ぎたイベント処理
 * @note エラー時は、例外を投げること
 * @see CVSDueDateHasPassedJob::handle 呼び出される場所
 * @see CVSDueDateHasPassedJob::failed 例外で呼ばれる
 */
namespace Kaoken\VeritransJpAirWeb\Events;


use Illuminate\Queue\SerializesModels;
use Kaoken\VeritransJpAirWeb\VeritransJpAirWebPaymentNotification;

/**
 * @see paymentNotification
 * @param \Kaoken\VeritransJpAirWeb\VeritransJpAirWebPayment $obj
 * @throws \Exception
 */
class CVSDueDateHasPassedEvent
{
    use SerializesModels;
    /**
     * @var VeritransJpAirWebPayment または、派生したクラス
     */
    public $obj;

    /**
     * PaymentNotificationEvent constructor.
     * @param VeritransJpAirWebPayment $obj 派生した物など
     */
    public function __construct($obj)
    {
        $this->obj = $obj;
    }
}