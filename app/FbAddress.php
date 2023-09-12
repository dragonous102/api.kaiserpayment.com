<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FbAddress extends Model
{
  use SoftDeletes;
  protected $table = 'fb_addresses';

  protected $fillable = [
    'address',
    'legacy_address',
    'asset_id',
    'vault_account_id',
    'vault_account_name',
    'active',
  ];

  /**
   * Define any additional model methods or relationships here.
   */

}
