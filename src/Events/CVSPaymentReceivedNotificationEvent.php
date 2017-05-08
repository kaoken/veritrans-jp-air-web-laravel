<?php
/**
 * コンビニエンスストア、入金通知イベントクラス
 */
namespace Kaoken\VeritransJpAirWeb\Events;


use Illuminate\Queue\SerializesModels;
use Kaoken\VeritransJpAirWeb\VeritransJpAirWebCvsPaymentNotification;


/**
 * Class AirWevCVSPaymentReceivedNotificationEvent
 */
class CVSPaymentReceivedNotificationEvent
{
    use SerializesModels;
    /**
     * @var VeritransJpAirWebCvsPaymentNotification
     */
    public $notification;

    public function __construct(VeritransJpAirWebCvsPaymentNotification $notification)
    {
        $this->notification = $notification;
    }
}