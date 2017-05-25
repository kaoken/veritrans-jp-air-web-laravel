<?php
/**
 * スケジュールメソッドで呼び出すための物
 * @see \App\Console\Kernel
 */
namespace Kaoken\VeritransJpAirWeb;


use Carbon\Carbon;
use Kaoken\VeritransJpAirWeb\Jobs\CVSDueDateHasPassedJob;

class VeritransJpAirWebScheduleTask
{
    /**
     * @var VeritransJpAirWebManager
     */
    protected $mgr;

    /**
     * VeritransJpAirWebScheduleTask constructor.
     * @param VeritransJpAirWebManager $mgr
     */
    public function __construct(VeritransJpAirWebManager $mgr)
    {
        $this->mgr = $mgr;
    }

    /**
     * 現在から`$day`過ぎた`air_web_payment`テーブルで
     * 決済完了通知が届いていない項目を削除する。
     * 参照先のカーネルクラスの`schedule`メソッドに
     *
     *    $schedule->call(function(){
     *        AirWeb::scheduleTask()->deleteNoPaymentNotification();
     *    })->dailyAt('00:00');
     *
     * のように追加する。
     * @see \App\Console\Kernel
     * @param int $day デフォルトで7
     * @return mixed
     */
    public function deleteNoPaymentNotification($day=7)
    {
        $d = Carbon::now()->subDays($day);
        $clCR = $this->mgr->getPaymentClass();
        $tblCR = (new $clCR())->getTable();

        // 決済完了通知が来ていないレコードを削除
        $clCR::whereNull('payment_notification_at')
            ->where('created_at','<',$d->format('Y-m-d H:i:s'))
            ->delete();


        // 決済完了通知があり、かつ失敗した
        $clPaymentN = $this->mgr->getPaymentNotificationClass();
        $tblPN = (new $clPaymentN())->getTable();

        $clCR::join($tblPN, $tblPN.'.order_id', '=', $tblCR.'.order_id')
            ->where($tblPN.'.status', 'failure')
            ->delete();
    }

    /**
     * コンビニ決済で支払期日が過ぎた決済をジョブへ
     *
     *    $schedule->call(function(){
     *        AirWeb::scheduleTask()->queueCVSDueDateHasPassed();
     *    })->dailyAt('00:00');
     *
     * のように追加する。
     * @param int $day 期日に加算する値。デフォルトで0
     * @note この時点で、レコードの削除はしないので、リスナー先で柵状等をすること。
     * @see \App\Console\Kernel
     * @see  CVSDueDateHasPassedJob
     */
    public function queueCVSDueDateHasPassed($day=0)
    {
        $d = Carbon::now()->subDays($day);
        $clPayment = $this->mgr->getPaymentClass();
        $list = $clPayment::where('timelimit_of_payment','<',$d->format('Y-m-d'))
            ->whereNotNull('payment_notification_at')
            ->whereNull('cvs_notification_at')
            ->where('settlement_type','02')
            ->get();

        // 期限切れを コンビニ通知期限切れジョブへ
        $clCvsPN = $this->mgr->getCVSDueDateHasPassedJobClass();
        foreach ($list as $item) {
            $job = new $clCvsPN($item);
            dispatch($job->onQueue('payment'));
        }
    }
}