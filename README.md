# veritrans-jp-air-web-laravel

[![TeamCity (simple build status)](https://img.shields.io/magnumci/ci/96ffb83fa700f069024921b0702e76ff/new-meta.svg)](https://github.com/kaoken/veritrans-jp-air-web-laravel)
[![composer version](https://img.shields.io/badge/version-0.0.0-blue.svg)](https://github.com/kaoken/veritrans-jp-air-web-laravel)
[![licence](https://img.shields.io/badge/licence-MIT-blue.svg)](https://github.com/kaoken/veritrans-jp-air-web-laravel)
[![php version](https://img.shields.io/badge/php%20version-≧5.6.4-red.svg)](https://github.com/kaoken/veritrans-jp-air-web-laravel)
[![laravel version](https://img.shields.io/badge/Laravel%20version-≧5.4-red.svg)](https://github.com/kaoken/veritrans-jp-air-web-laravel)


これは、[日本ベリトランス](https://www.veritrans.co.jp)の決済システム [AirWeb](https://www.veritrans.co.jp/developer/air/)をLaravelで使用するための物です。
詳しいAirWebに関しての実装方法などは https://www.veritrans.co.jp/developer/air/ を参照  
  
* 実験的に作っている物なので、このライブラリを通告なしで突然削除するかもしれない。  
* このライブラリに関して日本ベリトランスとは関係ないので、質問、お問い合わせをしないこと。  
* 仕様上、カードとコンビニ決済は、同時に使用できない。決済方法 '00' がそれにあたる。
* 今のところ詳しい使用方法は説明しない。


__コンテンツの一覧__

- [インストール](#インストール)
- [初期設定](#初期設定)
- [コンフィグ](#コンフィグ)
- [ミドルウェア](#ミドルウェア)
- [イベント](#イベント)
- [コントローラー](#コントローラー)
- [ルート](#ルート)
- [ライセンス](#ライセンス)

## インストール

**composer**:
**`composer.json` に以下のように追加：**

```js
  "require": {
    "kaoken/veritrans-jp-air-web-laravel":"dev-master"
  },
```


## 初期設定
### キュー
[キュー](https://readouble.com/laravel/5.4/ja/queues.html)を使用するので、`config/queue.php`で、**必ず**有効化すること！

```bash
例 php artisan queue:work --queue=payment,default --sleep=3 --tries=3
```
この辺は、環境に合わせて設定を！

### **`app\Console\Kernel.php` に以下のように追加：**

```php
class Kernel extends ConsoleKernel
{
    protected $commands = [
        // 追加
        \Kaoken\VeritransJpAirWeb\Console\MakeVeritransJpAirWebCommand::class,
    ];
}
```

### **`config\app.php` に以下のように追加：**

```php
    'providers' => [
        // 追加
        Kaoken\VeritransJpAirWeb\VeritransJpAirWebServiceProvide::class
    ],
    'aliases' => [
        // 追加
       'WebAir' => Kaoken\VeritransJpAirWeb\Facades\VeritransJpAirWeb::class
    ],
];
```

#### **コマンドの実行**

```bash
$ php artisan veritrans-jp:web-air:install
```
下記の4つのファイルは`database\migrations`へ追加される。
* `2017_04_24_000000_create_air_web_payment_table.php`
  * AirWeb決済情報テーブル
* `2017_04_24_000001_create_air_web_commodity_table.php`
  * 単体の商品情報テーブル
* `2017_04_24_000002_create_air_web_payment_notification_table.php`
  * 決済完了通知情報テーブル
* `2017_04_24_000003_create_air_web_cvs_payment_notification_table.php`
  * コンビニ入金通知情報テーブル

個々のWebアプリに合わせて追加修正をすること。  その後
  
```bash
$ php artisan migrate
```
※ ディレクトリの`config`へ`veritrans-jp-air-web.php`というコンフィグファイルが作成される。

## コンフィグ
`config\veritrans-jp-air-web.php`
```php
<?php
return [
    // マーチャントID
    'aw_merchant_id' => env('AW_MERCHANT_ID'),
    // AWへ送信するデータの検証用ハッシュキー
    'aw_merchant_hash_key' => env('AW_MERCHANT_HASH_KEY'),
    // ダミー取引フラグ ダミー取引フラグ 0 = 本番; 1 = テスト
    'aw_dummy_payment_flag' => env('AW_DUMMY_PAYMENT_FLAG', 0),

    // 売り上げフラグ：1：与信・売上、0：与信のみ。指定が無い場合は、0
    'aw_card_capture_flag' => env('AW_CARD_CAPTURE_FLAG', 1),
    // コンビニ決済の支払期限(当日からX日後)
    'aw_cvs_payment_limit' => env('AW_CVS_PAYMENT_LIMIT', 60),

    // 商品情報の商品ID未入力時に設定するダミー値
    'aw_dummy_commodity_id' => env('AW_DUMMY_COMMODITY_ID', 0),
    // 品情報のJAN_CODE未入力時に設定するダミー値
    'aw_dummy_commodity_jan_code' => env('AW_DUMMY_COMMODITY_JAN_CODE', '0'),
    // デフォルト決済方式。01 = クレジットカード、02 = コンビニ
    'aw_settlement_type' => env('AW_SETTLEMENT_TYPE', '01'),

    /**
     * 派生した場合は、クラスを変更すること
     */
    // 決済クラス
    'aw_payment_class' =>  \Kaoken\VeritransJpAirWeb\VeritransJpAirWebPayment::class,
    // 単体の商品クラス
    'aw_commodity_class' => \Kaoken\VeritransJpAirWeb\VeritransJpAirWebCommodity::class,
    // 決済完了通知クラス
    'aw_payment_notification_class' =>  \Kaoken\VeritransJpAirWeb\VeritransJpAirWebPaymentNotification::class,
    // コンビニ入金通知クラス
    'aw_cvs_payment_notification_class' =>  \Kaoken\VeritransJpAirWeb\VeritransJpAirWebCVSPaymentNotification::class,
    // 決済完了通知ジョブクラス
    'aw_payment_notification_job_class' =>  \Kaoken\VeritransJpAirWeb\Jobs\PaymentNotificationJob::class,
    // コンビニ入金通知ジョブクラス
    'aw_cvs_payment_notification_job_class' =>  \Kaoken\VeritransJpAirWeb\Jobs\CVSPaymentReceivedNotificationJob::class
];
```
※ `aw_settlement_type`は、'00'選択不可能で、カードかコンビニのみ。

### env
`env` ファイルに必要に応じて追加。
```txt
# Air Webへ送信するマーチャントID
AW_MERCHANT_ID=
# Air Webへ送信するデータの検証用ハッシュキー
AW_MERCHANT_HASH_KEY=
# ダミー取引フラグ ダミー取引フラグ 0 = 本番; 1 = テスト
AW_DUMMY_PAYMENT_FLAG=1
# コンビニ決済の支払期限(当日からX日後)
AW_CVS_PAYMENT_LIMIT=7
```


### タスクスケジュール
`app\Console\Kernel.php`
```php
    protected function schedule(Schedule $schedule)
    {
        ...
        $schedule->call(function(){
            AirWeb::scheduleTask()->deleteNoPaymentNotification();
            AirWeb::scheduleTask()->queueCVSDueDateHasPassed();
        })->dailyAt('00:00');
    }
```
* `AirWeb::deleteNoPaymentNotification($day=7)`は、
現在から`$day`日過ぎた`air_web_payment`テーブルで 決済完了通知が届いていないレコードまたは、
通知が着たが内容が失敗していた場合削除する。
* `AirWeb::eventCVSPaymentReceivedNotification($day=1)`は、
現在からコンビニ支払期日が`$day`日過ぎたジョブをキューに入れる。その後イベントが呼び出され、Webアプリごとに調整する。


## ミドルウェア
### **`app\Http\Kernel.php` に以下のように追加：**
```php
    protected $routeMiddleware = [
        ...
        'access_via_veritrans_jp' => \Kaoken\VeritransJpAirWeb\Middleware\AccessViaVeritransJp::class
    ];
```
このルートミドルウェアは、**決済完了通知**、**コンビニ入金通知**などで、VeritransJp経由だけを許す為に使用する。  
使用するかしないかは、個々に任せる。

## イベント
* `Kaoken\VeritransJpAirWeb\Events\CVSDueDateHasPassed`
  * `Kaoken\VeritransJpAirWeb\Jobs\CVSDueDateHasPassedJob`から呼び出される。
  * コンビニ決済で、入金期日が過ぎたイベント
* `Kaoken\VeritransJpAirWeb\Events\CVSPaymentReceivedNotificationEvent`
  * `Kaoken\VeritransJpAirWeb\Jobs\CVSPaymentReceivedNotificationJob`から呼び出される。
  * コンビニエンスストア、入金通知イベント
* `Kaoken\VeritransJpAirWeb\Events\PaymentNotificationEvent`
  * `Kaoken\VeritransJpAirWeb\Jobs\PaymentNotificationJob`から呼び出される。
  * 決済完了通知イベント

**下記は、使用テンプレート例** `app\Listeners`へ追加
```PaymentEventSubscriber.php 
<?php
/**
 * 決済 リスナー
 */
namespace App\Listeners;

use AirWeb;
use Log;
use Carbon\Carbon;
use Kaoken\VeritransJpAirWeb\Events\ CVSDueDateHasPassed;
use Kaoken\VeritransJpAirWeb\Events\CVSPaymentReceivedNotificationEvent;
use Kaoken\VeritransJpAirWeb\Events\PaymentNotificationEvent;

class PaymentEventSubscriber
{
    /**
     * 購読するリスナーの登録
     * @param \Illuminate\Events\Dispatcher $events
     */
    public function subscribe($events)
    {
        // コンビニ入金期日を過ぎた
        $events->listen(
            'Kaoken\VeritransJpAirWeb\Events\CVSDueDateHasPassed',
            'App\Listeners\PaymentEventSubscriber@onCVSDueDateHasPassed'
        );
        // コンビニエンスストア、入金通知
        $events->listen(
            'Kaoken\VeritransJpAirWeb\Events\CVSPaymentReceivedNotificationEvent',
            'App\Listeners\PaymentEventSubscriber@onCVSPaymentReceivedNotification'
        );
        // 決済完了通知
        $events->listen(
            'Kaoken\VeritransJpAirWeb\Events\PaymentNotificationEvent',
            'App\Listeners\PaymentEventSubscriber@onPaymentNotification'
        );
    }

    /**
     * コンビニ入金期日を過ぎた
     * @param CVSDueDateHasPassed $event
     */
    public function onCVSDueDateHasPassed(CVSDueDateHasPassed $event)
    {

    }

    /**
     * コンビニエンスストア、入金通知
     * @param CVSPaymentReceivedNotificationEvent $event
     * @throws \Exception 
     * @note 例外後、`failed_jobs`テーブルへ追加される。
     */
    public function onCVSPaymentReceivedNotification(CVSPaymentReceivedNotificationEvent $event)
    {
        $obj = $event->obj;

    }

    /**
     * 決済完了通知
     * @param PaymentNotificationEvent $event
     * @throws \Exception
     */
    public function onPaymentNotification(PaymentNotificationEvent $event)
    {
        $obj = $event->obj;

    }
}
```
個々のWebアプリごとに設定する。例えば、入金後、商品の発送処理などの処理をする。   

失敗イベント時の処理例 `app\Providers\AppServiceProvider.php`へ
```php
<?php

namespace App\Providers;

use Illuminate\Queue\Events\JobFailed;
use Kaoken\VeritransJpAirWeb\Jobs\CVSDueDateHasPassedJob;
use Kaoken\VeritransJpAirWeb\Jobs\CVSPaymentReceivedNotificationJob;
use Kaoken\VeritransJpAirWeb\Jobs\PaymentNotificationJob;
use Illuminate\Support\ServiceProvider;

use Log;
use Queue;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Queue::failing(function (JobFailed $event){
            // Air Web Veritrans Jp
            if( $event->connectionName === 'payment' ){
                $e = $event->exception;
                $a['error']['msg'] = $e->getMessage();
                $a['error']['code'] = $e->getCode();
                $a['error']['file'] = $e->getFile();
                $a['error']['line'] = $e->getLine();
                $a['error']['trace'] = $e->getTrace();

                if( $event->job instanceof CVSDueDateHasPassedJob){
                    $a['obj'] = $event->job->obj;
                    Log::error("Veritrans Jp コンビニ決済で、入金期日が過ぎた",$a);
                }else if( $event->job instanceof CVSPaymentReceivedNotificationJob){
                    $a['item'] = $event->job->items;
                    Log::error("Veritrans Jp コンビニ入金通知",$a);
                }else if( $event->job instanceof PaymentNotificationJob){
                    $a['item'] = $event->job->items;
                    Log::error("Veritrans Jp 決済完了通知",$a);
                }
            }
        });
    }
}
```
ここでは、ログのみだが、失敗時メール送信など追加してもよい。



## コントローラー
トレイトの`Kaoken\VeritransJpAirWeb\CVSPaymentReceivedNotification`と`Kaoken\VeritransJpAirWeb\PaymentNotification`
を追加し、Veritans Jp Air Webからの通知受け取るようにする。

下記は、`app\Http\Controllers\AirWebController.php`へ追加した例である。
```php
<?php
/**
 * Veritans Jp Air Web に関する処理
 * @see https://air.veritrans.co.jp/map/settings/service_settings
 */
namespace App\Http\Controllers;

use Log;
use \Illuminate\Http\Request;
use App\Library\Http\Controllers\Controller;
use Kaoken\VeritransJpAirWeb\CVSPaymentReceivedNotification;
use Kaoken\VeritransJpAirWeb\PaymentNotification;

class AirWebController extends Controller
{
    use PaymentNotification, CVSPaymentReceivedNotification;

    /**
     * 決済完了通知
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function postPaymentNotification(Request $request)
    {
        return $this->paymentNotification($request);
    }


    /**
     * コンビニ入金通知
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function postCVSPaymentReceivedNotification(Request $request)
    {
        return $this->cvsPaymentReceivedNotification($request);
    }


    /**
     * 決済完了後の移動先
     * @param Request $request
     * @param int $threadId スレッドID
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function postFinishPayment(Request $request, $threadId)
    {
//        Log::info('決済完了後');
        return redirect('/');
    }

    /**
     * 未決済時の移動先
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function getUnFinishPayment(Request $request)
    {
        return redirect('/');

    /**
     * 決済エラー時の移動先
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function postErrorPayment(Request $request)
    {
        return redirect('/');
    }
}
```

## ルート
[コントローラー](#コントローラー)の構成を元に作った例
`routes\web.php`へ追加した例
```php
Route::group([
        'middleware' => ['access_via_veritrans_jp']
    ]
    function() {
        // 決済完了通知を受信するためのもの
        Route::post('notification/handling','AirWebController@postPaymentNotification' );
        // コンビニ入金通知を受信するためのもの
        Route::post('cvs-payment-received','AirWebController@postCVSPaymentReceivedNotification' );
    }
);
Route::group([],
    function() {
        // 正常に支払い手続きが終了した購入者へ表示するURL
        Route::post('payment/finish','AirWebController@postFinishPayment' );
        // 決済入力画面から「戻る」をクリックした購入者へ表示する
        Route::get('payment/unfinish'AirWebController@getUnFinishPayment' );
        // 正常に支払い手続きが終了しなかった購入者へ表示する
        Route::post('payment/error','AirWebController@postErrorPayment' );
    }
);
```
ミドルウェアを使用しない場合は、ミドルウェア`access_via_veritrans_jp`を空に。

## ライセンス

[MIT](https://github.com/markdown-it/markdown-it/blob/master/LICENSE)