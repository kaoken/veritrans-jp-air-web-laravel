<?php
/**
 * 決済完了通知イベント
 * コントローラーで使用すること
 */
namespace Kaoken\VeritransJpAirWeb;

use AirWeb;
use Carbon\Carbon;
use Log;
use Validator;
use Illuminate\Http\Request;
use Kaoken\VeritransJpAirWeb\Events\PaymentNotificationEvent;


trait PaymentNotification
{

    /**
     * Veritrans Jp AirWeb で、決済完了通知を受け取り、正しく処理ができた場合は HTTPステータスコード200を返す。
     * このとき、成功した場合、一度キューに入れる
     * @see PaymentNotificationJob
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    protected function paymentNotification(Request $request)
    {
        $all = $request->all();

        // <editor-fold desc="Validatorは、後で削除するかもしれない">
        $validator = Validator::make($all,[
            'orderId' => 'required|max:100|regex:/^[-_\w]+$/',
            'mStatus' => 'required|string|max:32|regex:/^[\w]+$/',
            'vResultCode' => 'required|string|max:16|regex:/^[\w]+$/',
            'mErrMsg' => 'required',
            'merchantEncryptionKey' => 'required|regex:/^[-+_\w\/]+$/'
        ]);
        // merchantEncryptionKeyは、記号がさらに +/ が必要 Base64 フォーマットかな
        if ($validator->fails()) {
            $a['post'] = $all;
            $a['error'] = $validator->errors()->all();
            Log::error("Veritrans Jp 決済完了通知 [Validator]", $a);
            return response('',400);
        }
        // </editor-fold>


        $class = AirWeb::getPaymentNotificationJobClass();

        $job = new $class($all, $request->ip());
        dispatch($job->onQueue('payment'));

        //Log::info("Veritrans Jp 決済完了通知");
        return response('',200);
    }
}