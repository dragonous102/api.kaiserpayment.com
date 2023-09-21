<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FbDepositOrderAddress extends Model
{
  use SoftDeletes;
  protected $table = 'fb_deposit_order_address';

  protected $fillable = [
    'deposit_order_id',
    'address_id',
    'fee_amount',
    'prev_amount',
    'after_amount',
    'net_amount',
    'payment_status',
    'action_status',
    'description',
    'related_address',
    'txid',
  ];

  /**
   * Define any additional model methods or relationships here.
   */

  public function depositOrder(): BelongsTo
  {
    return $this->belongsTo(FbDepositOrder::class, 'deposit_order_id');
  }

  public function address(): BelongsTo
  {
    return $this->belongsTo(FbAddress::class, 'address_id');
  }
}
