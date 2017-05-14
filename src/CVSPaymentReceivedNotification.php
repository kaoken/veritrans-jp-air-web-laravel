<?php
/**
 * コンビニエンスストア、入金通知トレイト
 */
namespace Kaoken\VeritransJpAirWeb;

use AirWeb;
use Validator;
use Log;
use Illuminate\Http\Request;
use Kaoken\VeritransJpAirWeb\Events\CVSPaymentReceivedNotificationEvent;

/**
 * Trait AirWevCVSPaymentReceivedNotification
 */
trait CVSPaymentReceivedNotification
{
    /**
     * @see https://www.veritrans.co.jp/developer/air/api.html  コンビニ決済入金通知
     * @param Request $request
     */
    protected function cvsPaymentReceivedNotification(Request $request)
    {
        $all = $request->all();
//        Log::info("Veritrans Jp コンビニ入金通知");

        // <editor-fold desc="Validatorは、後で削除するかもしれない">
//        $validator = Validator::make($all,[
//            'numberOfNotify' => 'required|integer',
//            'pushTime' => 'required|regex:/^[0-9]{14}$/',
//            'pushId' => 'required|regex:/^[0-9]{8}$/',
//            'receiptDate*' => 'required|max:14|regex:/^[0-9]+$/',
//            'rcvAmount*' => 'required|max:6|regex:/^[0-9]+$/',
//            'dummy*' => 'required|max:1|regex:/^[0-9]+$/'
//        ]);
//
//        if ($validator->fails()) {
//            $a['post'] = $all;
//            $a['error'] = $validator->errors()->all();
//            Log::error("Veritrans Jp コンビニ入金通知 [Validator]", $a);
//            return response('',400);
//        }
//        // 下記のValidatorも、後で削除するかもしれない
//        $fANS = function ($key,$no,$len) use(&$all){
//            if( !preg_match("/^[-_\w]+$/",$all[$key.$no]) || strlen($all[$key.$no])>$len)
//                throw new \Exception($key.$no."が不正。");
//        };
//        $fN = function ($key,$no,$len) use(&$all){
//            if( !preg_match("/^[0-9]+$/",$all[$key.$no]) || strlen($all[$key.$no])>$len)
//                throw new \Exception($key.$no."が不正。");
//        };
//        for($i=0;$i<$all['numberOfNotify'];$i++){
//            $no = sprintf("%04d",$i);
//            try{
//                $fANS('orderId', $no,100);
//                $fANS('cvsType',$no,10);
//                $fANS('receiptNo', $no,32);
//                $fN('receiptDate', $no,14);
//                $fN('rcvAmount', $no,6);
//                $fN('dummy', $no,1);
//            }catch (\Exception $e){
//                $a['post'] = $all;
//                $a['error']['msg'] = $e->getMessage();
//                $a['error']['code'] = $e->getCode();
//                $a['error']['file'] = $e->getFile();
//                $a['error']['line'] = $e->getLine();
//                $a['error']['trace'] = $e->getTrace();
//                Log::error("Veritrans Jp コンビニ入金通知 [Validator]", $a);
//                return response('',400);
//            }
//        }
        // </editor-fold>
        // コンビニ入金通知ジョブクラスの取得
        $class = AirWeb::getCvsPaymentNotificationJobClass();

        // 複数通知がある場合、分割する
        for($i=0;$i<$all['numberOfNotify'];$i++){
            $a = [];
            $a['pushTime']      = $all['pushTime'];
            $a['pushId']        = $all['pushId'];

            $no = sprintf("%04d",$i);
            $a['orderId']       = $all['orderId'.$no];
            $a['cvsType']       = $all['cvsType'.$no];
            $a['receiptNo']     = $all['receiptNo'.$no];
            $a['receiptDate']   = $all['receiptDate'.$no];
            $a['rcvAmount']     = $all['rcvAmount'.$no];
            $a['dummy']         = $all['dummy'.$no];
            $job = new $class($a);
            dispatch($job->onQueue('payment'));
        }

        //Log::info("Veritrans Jp コンビニ入金通知 完了");
        return response('',200);
    }
}