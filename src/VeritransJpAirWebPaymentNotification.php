<?php
/**
 * 決済完了通知
 */

namespace Kaoken\VeritransJpAirWeb;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use WebAir;

class VeritransJpAirWebPaymentNotification extends Model
{
    protected $table = 'air_web_payment_notification';
    /**
     * 複数代入する属性
     *
     * @var array
     */
    protected $fillable = [
        'order_id', 'status',
        'result_code','err_msg',
        'merchant_encryption_key'
    ];
    public $timestamps = false;
    public $incrementing = false;


    /**
     * 日付により変更を起こすべき属性
     *
     * @var array
     */
    protected $dates = [
        'created_at'  // 作成日時
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