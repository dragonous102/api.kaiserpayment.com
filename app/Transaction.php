<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
  use SoftDeletes;

  protected $primaryKey = 'orderNo';
  public $incrementing = false;

  protected $fillable = [
    'partner_id',
    'email_address',
    'card_holder_name',
    'orderNo',
    'amount',
    'fee',
    'fee_percent',
    'product_name',
    'status',
    'partner_domain',
  ];

  // Define the relationship with the Partner model
  public function partner()
  {
    return $this->belongsTo(Partner::class);
  }
}
