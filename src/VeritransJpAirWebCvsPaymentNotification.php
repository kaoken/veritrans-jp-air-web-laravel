<?php
/**
 * 決済完了通知
 */

namespace Kaoken\VeritransJpAirWeb;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use AirWeb;

class VeritransJpAirWebCVSPaymentNotification extends Model
{
    protected $table = 'air_web_cvs_payment_notification';
    protected $primaryKey = 'order_id';
    public $incrementing = false;
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

    /**
     * コンビニエンスストアの正式の名前を返す
     * @return string
     */
    public function cvsTypeName()
    {
        if($this->csv_type === "sej") return "セブン－イレブン";
        elseif($this->csv_type === "econ-lw") return "ローソン";
        else if($this->csv_type === "econ-fm") return "ファミリーマート";
        elseif($this->csv_type === "econ-mini") return "ミニストップ";
        elseif($this->csv_type === "econ-other") return "セイコーマート";
        elseif($this->csv_type === "econ-ck") return "サークルK";
        elseif($this->csv_type === "econ-sn") return "サンクス";
        return "";
    }

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