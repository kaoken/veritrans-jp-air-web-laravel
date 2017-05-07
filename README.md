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
* 今のところ使用方法は説明しない。


__コンテンツの一覧__

- [インストール](#インストール)
- [初期設定](#初期設定)
- [コンフィグ](#コンフィグ)
- [ライセンス](#ライセンス)

## インストール

**composer**:

```bash
composer install kaoken/veritrans-jp-air-web-laravel
```

## 初期設定
**`app\Console\Kernel.php` に以下のように追加：**

```php
class Kernel extends ConsoleKernel
{
    protected $commands = [
        // 追加
        \Kaoken\VeritransJpAirWeb\Console\MakeVeritransJpAirWebCommand::class,
    ];
}
```

**`config\app.php` に以下のように追加：**

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

**コマンドの実行**

```bash
$ php artisan veritrans-jp:web-air:install
```
下記の4つのファイルは`database\migrations`へ追加される。
* `2017_04_24_000000_create_air_web_commodity_regist_table.php`
* `2017_04_24_000001_create_air_web_commodity_table.php`
* `2017_04_24_000002_create_air_web_payment_notification_table.php`
* `2017_04_24_000003_create_air_web_cvs_payment_notification_table.php`

個々のWebアプリに合わせて追加修正をすること。  その後
  
```bash
$ php artisan migrate
```


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
    // デフォルト決済方式。00 = 両方、01 = クレジットカード、02 = コンビニ
    'aw_settlement_type' => env('AW_SETTLEMENT_TYPE', '00'),

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
    // デフォルト決済方式。00 = 両方、01 = クレジットカード、02 = コンビニ
    'aw_settlement_type' => env('AW_SETTLEMENT_TYPE', '00'),

    /**
     * 派生した場合は、クラスを変更すること
     */
    // 商品登録クラス
    'aw_commodity_class' => \Kaoken\VeritransJpAirWeb\VeritransJpAirWebCommodity::class,
    // 単体の商品クラス
    'aw_commodity_register_class' =>  \Kaoken\VeritransJpAirWeb\VeritransJpAirWebCommodityRegister::class,
    // 決済完了通知クラス
    'aw_payment_notification_class' =>  \Kaoken\VeritransJpAirWeb\VeritransJpAirWebPaymentNotification::class,
    // コンビニ入金通知クラス
    'aw_cvs_payment_notification_class' =>  \Kaoken\VeritransJpAirWeb\VeritransJpAirWebCvsPaymentNotification::class
];
```

`env` ファイルに必要に応じて追加。
```txt
# Air Webへ送信するマーチャントID
AW_MERCHANT_ID=
# Air Webへ送信するデータの検証用ハッシュキー
AW_MERCHANT_HASH_KEY=
# ダミー取引フラグ ダミー取引フラグ 0 = 本番; 1 = テスト
AW_DUMMY_PAYMENT_FLAG=1
# コンビニ決済の支払期限(当日からX日後)
AW_CVS_PAYMENT_LIMIT=3
```
### ミドルウェア
`app\Http\Kernel.php`
```php
    protected $routeMiddleware = [
        ...
        'access_via_veritrans_jp' => \Kaoken\VeritransJpAirWeb\Middleware\AccessViaVeritransJp::class
    ];
```
このルートミドルウェアは、決済完了通知、コンビニ入金通知などで、VeritransJp経由だけを許す為に使用する。強制では無い。

## ライセンス

[MIT](https://github.com/markdown-it/markdown-it/blob/master/LICENSE)