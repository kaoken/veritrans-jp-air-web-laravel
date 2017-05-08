<?php
/**
 * 決済完了通知イベント
 * コントローラーで使用すること
 */
namespace Kaoken\VeritransJpAirWeb;

use AirWeb;
use Carbon\Carbon;
use Log;
use Illuminate\Http\Request;
use Kaoken\VeritransJpAirWeb\Events\PaymentNotificationEvent;

/**
 * Trait AirWevPaymentNotification
 */
trait PaymentNotification
{
    /**
     * `paymentNotification`メソッド内から呼び出される。エラー時は、例外を投げること
     * @see paymentNotification
     * @warning `$obj`は、このメソッドが呼ばれた後、`->save();`される。
     * @param \Kaoken\VeritransJpAirWeb\VeritransJpAirWebPaymentNotification $obj
     * @throws \Exception
     */
    abstract protected function paymentNotificationCall(&$obj);

    /**
     * Veritrans Jp AirWeb で、決済完了通知を受け取り、正しく処理ができた場合は HTTPステータスコード200を返す。
     * このとき、成功した場合、イベントを発生させている
     * @see PaymentNotificationEvent
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    protected function paymentNotification(Request $request)
    {
        $all = $request->all();
        $validator = Validator::make($all,[
            'orderId' => 'required',
            'mStatus' => 'required',
            'vResultCode' => 'required',
            'mErrMsg' => 'required',
            'merchantEncryptionKey' => 'required'
        ]);

        if ($validator->fails()) {
            $a['request'] = $request;
            $a['error'] = $validator->errors()->all();
            Log::error("Veritrans Jp 決済完了通知", $a);
            return response('',422);
        }
        $class = AirWeb::getPaymentNotificationClass();
        $obj = new $class();

        $obj->order_id      = $all['orderId'];
        $obj->status        = $all['mStatus'];
        $obj->result_code   = $all['vResultCode'];
        $obj->err_msg       = $all['mErrMsg'];
        $obj->merchant_encryption_key = $all['merchantEncryptionKey'];
        $obj->created_at    = Carbon::now();

        try{
            $this->paymentNotificationCall($obj);
            $obj->save();
        }catch (\Exception $e){
            $a['request'] = $request;
            $a['error'] = $e->getMessage();
            Log::error("Veritrans Jp 決済完了通知",$a);
            return response('',422);
        }

        Log::info("Veritrans Jp 決済完了通知", $request);
        event(new PaymentNotificationEvent($obj));
        return response('',200);
    }
}