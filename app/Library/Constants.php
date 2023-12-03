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
    'failed'=>'failed',
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

  public static $PARTNER_STATUS = [
    'enabled'=>1,
    'disabled'=>0,
  ];

  public static $UAT = 'UAT';
  public static $PROD = 'PROD';
}
