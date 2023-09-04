<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
  protected $fillable = [
    'name',
    'domain',
    'fee',
    'status',
  ];

  protected $table = 'partners';
}
