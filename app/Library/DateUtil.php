<?php
namespace App\Library;
use DateTime;
use DateTimeZone;
use Exception;

class DateUtil
{
  /**
   * @throws Exception
   */
  public static function convertToUTC($dateString): string
  {
    $date = new DateTime($dateString);
    $date->setTimezone(new DateTimeZone('UTC'));
    return $date->format('Y-m-d H:i:s');
  }
}
