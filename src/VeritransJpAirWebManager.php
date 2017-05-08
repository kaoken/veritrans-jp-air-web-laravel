<?php
/**
 * VeritransJp AirWeb 管理クラス
 */
namespace Kaoken\VeritransJpAirWeb;

use Carbon\Carbon;
use Illuminate\Support\Manager;
use GuzzleHttp\Client;
use Kaoken\VeritransJpAirWeb\Events\CVSPaymentDateHasPassedEvent;

class VeritransJpAirWebManager extends Manager
{

    /**
     * Get the default driver name.
     * @warning 現在使用できません
     * @return string
     */
    public function getDefaultDriver()
    {
        throw new InvalidArgumentException('Driver can not be specified.');
    }

    /**
     * 現在から`$day`過ぎた`air_web_commodity_register`テーブルで
     * 決済完了通知が届いていない項目を削除する。
     * 参照先のカーネルクラスの`schedule`メソッドに
     *
     *    $schedule->call(function(){
     *        AirWeb::deleteNoPaymentNotification();
     *    })->dailyAt('00:00');
     *
     * のように追加する。
     * @see \App\Console\Kernel
     * @param int $day デフォルトで1
     * @return mixed
     */
    public function deleteNoPaymentNotification($day=1)
    {
        $d = Carbon::now()->subDays($day);
        //$d = Carbon::now()->subMinutes($day); // テスト
        $clCR = $this->getCommodityRegisterClass();
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
     *        AirWeb::deleteCvsPaymentReceivedNotification();
     *    })->dailyAt('00:00');
     *
     * のように追加する。
     * @see \App\Console\Kernel
     * @see CVSPaymentDateHasPassedEvent
     */
    public function deleteCvsPaymentReceivedNotification($day=1)
    {
        $d = Carbon::now()->subDays($day);
        $clCR = $this->getCommodityRegisterClass();
        $list = $clCR::where('timelimit_of_payment','<',$d->format('Y-m-d'))
            ->whereNotNull('payment_notification_at')
            ->whereIn('settlement_type',['00','02'])
            ->get();
        foreach ($list as $item) {
            event( new CVSPaymentDateHasPassedEvent($item) );
            $item->delete();
        }
   }

    /**
     * このサービスのコンフィグデータを取得する
     * @param string $key
     * @return mixed
     */
    protected function getMyConfig($key)
    {
        return $this->app['config']->get('veritrans-jp-air-web.'.$key);
    }

    /**
     * 特殊文字、半角英数字カナなどを全角化する。
     * @param $str
     */
    protected function toD($str)
    {
        $str = mb_convert_kana($str,"RNASKV");
        $ret = "";
        $l = mb_strlen($str);
        for($i=0;$i<$l;$i++){
            $c = mb_substr($str,$i,1);
            if( strlen($c) === 1 ){
                if( $c === '"' ){
                    $ret.="”";
                }else if( $c === '~' ){
                    $ret.="～";
                }else if( $c === '\\' ){
                    $ret.="￥";
                }
            }else{
                $ret.=$c;
            }
        }
        return $ret;
    }

    /**
     * AirWevの暗号鍵入手する時に表と用となる要求電文を作成する.
     * ただし、属性値に全ての値が入っているものとする
     * @param VeritransJpAirWebCommodityRegister $o
     * @return array
     */
    protected function toAirWebPostFormat(VeritransJpAirWebCommodityRegister &$o)
    {
        $ret = [];
        /**
         * 必須項目
         */
        $ret['MERCHANT_ID']     = env('AW_MERCHANT_ID');
        $ret['MERCHANTHASH']    = $o->merchanthash;
        $ret['SESSION_ID']      = $o->session_id;
        $ret['SETTLEMENT_TYPE'] = $o->settlement_type;
        $ret['ORDER_ID']        = $o->order_id;
        $ret['AMOUNT']          = $o->amount;

        //
        if( !empty($o->shipping_amount) )
            $ret['SHIPPING_AMOUNT'] = $o->shipping_amount;

        // 戻り URLなど
        if( !is_null($o->finish_payment_return_url) )
            $ret['FINISH_PAYMENT_RETURN_URL'] = $o->finish_payment_return_url;
        if( !is_null($o->unfinish_payment_return_url) )
            $ret['UNFINISH_PAYMENT_RETURN_URL'] = $o->unfinish_payment_return_url;
        if( !is_null($o->error_payment_return_url) )
            $ret['ERROR_PAYMENT_RETURN_URL'] = $o->error_payment_return_url;
        if( !is_null($o->finish_payment_access_url) )
            $ret['FINISH_PAYMENT_ACCESS_URL'] = $o->finish_payment_access_url;


        $ret['DUMMY_PAYMENT_FLAG'] = env('AW_DUMMY_PAYMENT_FLAG',0);

        if( $o->settlement_type === '00' || $o->settlement_type === '02') {
            $ret['TIMELIMIT_OF_PAYMENT']       = $o->timelimit_of_payment->format('Ymd');
            $ret['NAME1']       = $this->toD($o->name1);
            $ret['NAME2']       = $this->toD($o->name2);
            $ret['KANA1']       = $this->toD($o->kana1);
            $ret['KANA2']       = $this->toD($o->kana2);
            $ret['ADDRESS1']    = $this->toD($o->address1);
            if( !is_null($o->address2) )
                $ret['ADDRESS2']= $this->toD($o->address2);
            if( !is_null($o->address3) )
                $ret['ADDRESS3']= $this->toD($o->address3);
            $ret['ZIP_CODE']    = preg_replace("/[^0-9|-]/","", $o->zip_code);
            $ret['TELEPHONE_NO']= preg_replace("/[^0-9]/","", $o->telephone_no);
            $ret['MAILADDRESS'] = $o->mailaddress;
            $ret['BIRTHDAY']    = $o->birthday->format('Ymd');
            $ret['SEX']         = $o->sex;
        }
        if( $o->settlement_type === '00' || $o->settlement_type === '01') {
            $ret['CARD_CAPTURE_FLAG'] = empty($o->card_capture_flag)?0:1;
        }

        $a = $o->airWebCommodity;
        if( count($a) > 0){
            $ret['COMMODITY_ID'] = [];
            $ret['COMMODITY_UNIT'] = [];
            $ret['COMMODITY_NUM'] = [];
            $ret['COMMODITY_NAME'] = [];
            $ret['JAN_CODE'] = [];
            foreach ($a as $item) {
                $ret['COMMODITY_ID'][]    = $item->commodity_id;
                $ret['COMMODITY_UNIT'][]  = $item->commodity_unit;
                $ret['COMMODITY_NUM'][]   = $item->commodity_num;
                $ret['COMMODITY_NAME'][]  = $this->toD($item->commodity_name);
                $ret['JAN_CODE'][]        = $item->jan_code;
            }
        }
        return $ret;
    }

    /**
     * オーダーを作成する
     * @param string|number $name オーダーIDの最初の値
     * @note `order_id`へ`$name`と`_`の次に現在に日時を接頭辞としてつけた文字列を渡す
     * @return VeritransJpAirWebCommodityRegister
     */
    public function createOrder($name)
    {
        $class = $this->getMyConfig('aw_commodity_register_class');
        $o = new $class();
        $o->order_id = $name.'_'.Carbon::now()->format('YmdHis');
        return  $o;
    }

    /**
     * 何の関連の無い空の商品を作成する
     * @return VeritransJpAirWebCommodity
     */
    public function createEmptyCommodity()
    {
        $class = $this->getMyConfig('aw_commodity_class');
        $o = new $class();
        return  $o;
    }

    /**
     * VeritransJp AirWeb から 暗号化キーを取得する。
     * 取得に失敗した場合は、戻り値のインスタンスで、`$o->err`が`true`になる。
     * @warning 商品をこの段階で追加されていること！
     * @param VeritransJpAirWebCommodityRegister $cr
     * @return object {code:{int},item:{array},err:{bool}}
     */
    public function getEncryptionKey(VeritransJpAirWebCommodityRegister &$cr)
    {
        $o = new \stdClass();
        $ary = $this->toAirWebPostFormat($cr);

        $post = [];
        foreach($ary as $key => $val) {
            if (is_array($val)) {
                foreach($val as $val2) $post[] = $key . '=' . urlencode($val2);
            } else {
                $post[] = $key . '=' . urlencode($val);;
            }
        }
        $postdata = implode("&", $post);

        $res = (new Client())->request(
            'POST',
            'https://air.veritrans.co.jp/web/commodityRegist.action',
            [
                'body' => $postdata,
                'headers'        => ['Content-Type' => 'application/x-www-form-urlencoded']
            ]
        );
        $cnt=0;
        $o->code = (int)$res->getStatusCode();
        $o->item = [];
        $o->err = false;
        if( $o->code == 200 ){
            $a = preg_split("/\n/",$res->getBody()->getContents());
            foreach ($a as $str){
                $b = preg_split("/=/",$str);
                if( count($b) <= 1 )continue;
                $o->item[$b[0]]=$b[1];
                if($b[0] === 'ERROR_MESSAGE') $o->err = true;
                else if($b[0] === 'BROWSER_ENCRYPTION_KEY') ++$cnt;
                else if($b[0] === 'MERCHANT_ENCRYPTION_KEY') ++$cnt;
            }
            $o->err = $cnt!==2;
            if(!$o->err){
                $cr->merchant_encryption_key = $o->item['MERCHANT_ENCRYPTION_KEY'];
                $cr->browser_encryption_key = $o->item['BROWSER_ENCRYPTION_KEY'];
            }
        }else{
            $o->err = true;
        }
        return $o;
    }

    // <editor-fold desc="クラスの取得">
    /**
     * オーダークラスを返す
     * @return string
     */
    public function getCommodityRegisterClass()
    {
        return $this->getMyConfig('aw_commodity_register_class');
    }

    /**
     * オーダー商品クラスを返す
     * @return string
     */
    public function getCommodityClass()
    {
        return $this->getMyConfig('aw_commodity_class');
    }

    /**
     * 決済完了通知クラスを返す
     * @return string
     */
    public function getPaymentNotificationClass()
    {
        return $this->getMyConfig('aw_payment_notification_class');
    }

    /**
     * コンビニ決済入金通知クラスを返す
     * @return string
     */
    public function getCvsPaymentNotificationClass()
    {
        return $this->getMyConfig('aw_cvs_payment_notification_class');
    }
    // </editor-fold>
}