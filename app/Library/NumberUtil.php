<?php
namespace App\Library;

class NumberUtil
{
  function roundToDynamicPrecision($number): float
  {
    // Calculate the precision dynamically based on the number
    $precision = max(-log10(abs($number)), 0);

    // Round the number to the calculated precision
    return round($number, $precision);
  }
}
