<?php
/**
 * コンビニエンスストア、入金通知イベントクラス
 */
namespace Kaoken\VeritransJpAirWeb\Events;


use Illuminate\Queue\SerializesModels;
use Kaoken\VeritransJpAirWeb\VeritransJpAirWebCvsPaymentNotification;


class CVSPaymentReceivedNotificationEvent
{
    use SerializesModels;
    /**
     * @var VeritransJpAirWebCvsPaymentNotification または、派生したクラス
     */
    public $obj;


    public function __construct($obj)
    {
        $this->obj = $obj;
    }
}