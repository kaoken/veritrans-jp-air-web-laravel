<?php
/**
 * スケジュールメソッドで呼び出すための物
 * @see \App\Console\Kernel
 */
namespace Kaoken\VeritransJpAirWeb;

class VeritransJpAirWebScheduleTask
{
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
        //$d = Carbon::now()->subMinutes($day); // テスト
        $clCR = $this->getPaymentClass();
        $tblCR = (new $clCR())->getTable();

        // 決済完了通知が来ていないレコードを削除
        $clCR::whereNull('payment_notification_at')
            ->where('created_at','<',$d->format('Y-m-d H:i:s'))
            ->delete();


        // 決済完了通知があり、かつ失敗した
        $clPN = $this->getPaymentNotificationClass();
        $tblPN = (new $clPN())->getTable();

        $clCR::join($tblPN, $tblPN.'.order_id', '=', $tblCR.'.order_id')
            ->where($tblPN.'.status', 'failure')
            ->delete();
    }

    /**
     * コンビニ決済で支払期日が過ぎたオーダーを削除する
     * そして、コンビニ決済の支払期日が過ぎたイベントも発生させる。
     *
     *    $schedule->call(function(){
     *        AirWeb::scheduleTask()->queueCvsDueDateHasPassed();
     *    })->dailyAt('00:00');
     *
     * のように追加する。
     * @see \App\Console\Kernel
     * @see CVSPaymentDateHasPassedEvent
     */
    public function queueCvsDueDateHasPassed($day=1)
    {
        $d = Carbon::now()->subDays($day);
        $clCR = $this->getPaymentClass();
        $list = $clCR::where('timelimit_of_payment','<',$d->format('Y-m-d'))
            ->whereNotNull('payment_notification_at')
            ->whereIn('settlement_type',['00','02'])
            ->get();
        foreach ($list as $item) {
            event( new CVSPaymentDateHasPassedEvent($item) );
            $item->delete();
        }
    }
}