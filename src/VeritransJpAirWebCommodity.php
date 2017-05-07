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

    /**
     * 複数代入する属性
     *
     * @var array
     */
    protected $fillable = [
        'order_id',
        'commodity_id','commodity_unit','commodity_num','commodity_name','jan_code'
    ];

    // <editor-fold desc="リレーション定義">
    /**
     * AirWebの商品群
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function airWebCommodityRegister()
    {
        return $this->belongsTo(AirWeb::getCommodityRegisterClass(), 'order_id', 'order_id');
    }
    // </editor-fold>

}