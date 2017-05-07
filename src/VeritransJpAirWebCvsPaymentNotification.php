<?php
/**
 * 決済完了通知
 */

namespace Kaoken\VeritransJpAirWeb;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use AirWeb;

class VeritransJpAirWebCvsPaymentNotification extends Model
{
    protected $table = 'air_web_cvs_payment_notification';
    public $incrementing = false;

    /**
     * 複数代入する属性
     *
     * @var array
     */
    protected $fillable = [
        'order_id', 'push_time',
        'push_id','csv_type',
        'receipt_no', 'receipt_date','rcv_amount', 'dummy'
    ];
    public $timestamps = false;


    /**
     * 日付により変更を起こすべき属性
     *
     * @var array
     */
    protected $dates = [
        'push_time',    // 通知した時刻
        'receipt_date'  // 消費者側で支払いが完了した時刻
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