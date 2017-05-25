<?php
/**
 * 取引IDごとの商品を表す
 */
namespace Kaoken\VeritransJpAirWeb;

use Illuminate\Database\Eloquent\Model;
use AirWeb;

/**
 * Class AirWebCommodity
 * @package Kaoken\VeritransJpAirWeb
 */
class VeritransJpAirWebCommodity extends Model
{
    protected $table = 'air_web_commodity';
    public $timestamps = false;
    public $incrementing = false;

    // <editor-fold desc="リレーション定義">
    /**
     * AirWebの商品群
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function airWebPayment()
    {
        return $this->belongsTo(AirWeb::getPaymentClass(), 'order_id', 'order_id');
    }
    // </editor-fold>

}