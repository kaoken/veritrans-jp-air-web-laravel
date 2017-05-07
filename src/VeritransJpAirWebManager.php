<?php
/**
 * VeritransJp AirWeb 管理クラス
 */
namespace Kaoken\VeritransJpAirWeb;

use Carbon\Carbon;
use Illuminate\Support\Manager;
use GuzzleHttp\Client;

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
        $class = $this->getCommodityRegisterClass();
        return $class::whereNull('payment_notification_at')
            ->where('created_at','<',$d->format('Y-m-d H:i:s'))
            ->delete();
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
            $ret['NAME1']       = $o->name1;
            $ret['NAME2']       = $o->name2;
            $ret['KANA1']       = $o->kana1;
            $ret['KANA2']       = $o->kana2;
            $ret['ADDRESS1']    = $o->address1;
            if( !is_null($o->address2) )
                $ret['ADDRESS2']= $o->address2;
            if( !is_null($o->address3) )
                $ret['ADDRESS3']= $o->address3;
            $ret['ZIP_CODE']    = $o->zip_code;
            $ret['TELEPHONE_NO']= preg_replace("/[^0-9]/","", $user->telephone_no);
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
                $ret['COMMODITY_NAME'][]  = $item->commodity_name;
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
     * @param VeritransJpAirWebCommodityRegister $cr
     * @return object {code:{int},item:{array},err:{bool}}
     */
    public function getEncryptionKey(VeritransJpAirWebCommodityRegister &$cr)
    {
        $o = new \stdClass();
        $ary = $this->toAirWebPostFormat($cr);

        $res = (new Client())->request(
            'POST',
            'https://air.veritrans.co.jp/web/commodityRegist.action',
            ['form_params'=>$ary]
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