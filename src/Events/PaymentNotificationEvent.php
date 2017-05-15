<?php
/**
 * 決済完了通知イベント
 * `PaymentNotificationJob::handle`メソッド内から呼び出される。エラー時は、例外を投げること
 * @warning このイベントが呼び終わった後、`$obj->save();`される。
 * @note エラー時は、例外を投げること
 * @see PaymentNotificationJob::handle 呼び出される場所
 * @see PaymentNotificationJob::failed 例外で呼ばれる
 */
namespace Kaoken\VeritransJpAirWeb\Events;


use Illuminate\Queue\SerializesModels;
use Kaoken\VeritransJpAirWeb\VeritransJpAirWebPaymentNotification;

/**
 * @see paymentNotification
 * @param \Kaoken\VeritransJpAirWeb\VeritransJpAirWebPaymentNotification $obj
 * @throws \Exception
 */
class PaymentNotificationEvent
{
    use SerializesModels;
    /**
     * @var VeritransJpAirWebPaymentNotification または、派生したクラス
     */
    public $obj;

    /**
     * PaymentNotificationEvent constructor.
     * @param VeritransJpAirWebPaymentNotification $obj 派生した物など
     */
    public function __construct($obj)
    {
        $this->obj = $obj;
    }
}