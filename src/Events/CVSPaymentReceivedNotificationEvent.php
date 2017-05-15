<?php
/**
 * コンビニエンスストア、入金通知イベントクラス
 * @warning このイベントが呼び終わった後、`$obj->save();`される。
 * @note エラー時は、例外を投げること
 * @see CVSPaymentReceivedNotificationJob::handle 呼び出される場所
 * @see CVSPaymentReceivedNotificationJob::failed 例外で呼ばれる
 */
namespace Kaoken\VeritransJpAirWeb\Events;


use Illuminate\Queue\SerializesModels;
use Kaoken\VeritransJpAirWeb\VeritransJpAirWebCVSPaymentNotification;


class CVSPaymentReceivedNotificationEvent
{
    use SerializesModels;
    /**
     * @var VeritransJpAirWebCVSPaymentNotification または、派生したクラス
     */
    public $obj;


    public function __construct($obj)
    {
        $this->obj = $obj;
    }
}