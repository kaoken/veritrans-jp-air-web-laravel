<?php

namespace Kaoken\VeritransJpAirWeb\Facades;

use Illuminate\Support\Facades\Facade;

class VeritransJpAirWeb extends Facade
{
    /**
     * コンポーネントの登録された名前を取得します。
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'veritrans-jp-air-web';
    }
}
