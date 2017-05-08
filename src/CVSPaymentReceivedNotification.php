<?php
/**
 * コンビニエンスストア、入金通知トレイト
 */
namespace Kaoken\VeritransJpAirWeb;

use AirWeb;
use Illuminate\Http\Request;
use Kaoken\VeritransJpAirWeb\Events\CVSPaymentReceivedNotificationEvent;

/**
 * Trait AirWevCVSPaymentReceivedNotification
 */
trait CVSPaymentReceivedNotification
{

    protected function cvsPaymentReceivedNotification(Request $request)
    {

    }
}