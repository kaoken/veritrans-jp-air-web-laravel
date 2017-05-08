<?php
/**
 * 決済完了通知イベント
 * コントローラーで使用すること
 */
namespace Kaoken\VeritransJpAirWeb;

use AirWeb;
use Illuminate\Http\Request;
use Kaoken\VeritransJpAirWeb\Events\PaymentNotificationEvent;

/**
 * Trait AirWevPaymentNotification
 */
trait PaymentNotification
{
    protected function paymentNotification(Request $request)
    {

    }
}