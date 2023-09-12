<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Partner extends Model
{
  use SoftDeletes;
  protected $fillable = [
    'name',
    'domain',
    'fee',
    'crypto_fee',
    'status',
  ];

  protected $table = 'partners';
}
