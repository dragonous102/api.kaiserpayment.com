<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FbDepositOrder extends Model
{
  use SoftDeletes;
  protected $table = 'fb_deposit_order';

  protected $fillable = [
    'order_id',
    'partner_id',
    'amount',
    'product_name',
    'currency',
    'email',
    'name',
  ];

  public function partner(): BelongsTo
  {
    return $this->belongsTo(Partner::class, 'partner_id');
  }

  public static function calculateTotalAmount()
  {
    return self::sum('amount');
  }
}
