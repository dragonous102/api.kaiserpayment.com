<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FbCronJobMonitor extends Model
{
  use SoftDeletes;

  protected $table = 'fb_cron_job_monitor';

  protected $fillable = [
    'duration',
  ];
}
