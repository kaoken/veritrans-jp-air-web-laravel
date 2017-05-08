<?php
/**
 * 決済完了通知イベント
 */
namespace Kaoken\VeritransJpAirWeb\Events;


use Illuminate\Queue\SerializesModels;
use Kaoken\VeritransJpAirWeb\VeritransJpAirWebPaymentNotification;

/**
 * Class AirWevPaymentNotificationEvent
 */
class PaymentNotificationEvent
{
    use SerializesModels;
    /**
     * @var VeritransJpAirWebPaymentNotification
     */
    public $notification;

    public function __construct(VeritransJpAirWebPaymentNotification $notification)
    {
        $this->notification = $notification;
    }
}