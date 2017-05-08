<?php
/**
 * コンビニエンスストア、の支払期日を過ぎた時のイベント
 * これは、
 * @see AirWeb::
 */
namespace Kaoken\VeritransJpAirWeb\Events;


use Illuminate\Queue\SerializesModels;


class CVSPaymentDateHasPassedEvent
{
    use SerializesModels;
    /**
     * @var VeritransJpAirWebCommodityRegister
     */
    public $commodityRegister;

    /**
     * コンビニでの支払期日を過ぎた オーダーIDをセットする
     * @param VeritransJpAirWebCommodityRegister $commodityRegister
     */
    public function __construct($commodityRegister)
    {
        $this->commodityRegister = $commodityRegister;
    }
}