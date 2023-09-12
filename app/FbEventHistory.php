<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FbEventHistory extends Model
{
  use SoftDeletes;
  protected $table = 'fb_event_history';

  protected $fillable = [
    'deposit_order_id',
    'address_id',
    'fee_amount',
    'prev_amount',
    'after_amount',
    'net_amount',
    'type',
    'status',
    'result',
    'reason',
    'description',
    'related_address',
  ];

  /**
   * Define any additional model methods or relationships here.
   */

  public function depositOrder()
  {
    return $this->belongsTo(FbDepositOrder::class, 'deposit_order_id');
  }

  public function address()
  {
    return $this->belongsTo(FbAddress::class, 'address_id');
  }
}
