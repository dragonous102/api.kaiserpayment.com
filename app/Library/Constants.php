<?php
namespace App\Library;
use App\Partner;
use Exception;
use Firebase\JWT\JWT;

class Constants
{
  public static $PAYMENT_STATUS = [
    'pending'=>'pending',
    'complete'=>'complete',
    'over'=>'over',
  ];

  public static $ACTION_STATUS = [
    'success'=>'success',
    'failed'=>'failed',
  ];

  public static $CREATED_BY = [
    'system'=>'system',
    'user'=>'user',
    'admin'=>'admin',
  ];
}
