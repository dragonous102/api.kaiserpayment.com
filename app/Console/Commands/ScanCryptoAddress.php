<?php

namespace App\Console\Commands;

use App\FbCronJobMonitor;
use App\FbDepositOrderAddress;
use App\Http\Controllers\FireBlocksController;
use App\Library\Constants;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;


class ScanCryptoAddress extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'kaiser:scan-crypto-address';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Check balances of all vault account address in fireblock';

  /**
   * Create a new command instance.
   *
   * @return void
   */
  public function __construct()
  {
    parent::__construct();
  }

  /**
   * Execute the console command.
   *
   * @return int
   */
  public function handle(): int
  {
    $startTime = now();

    $pendingOrders = FbDepositOrderAddress::where('payment_status', Constants::$PAYMENT_STATUS['pending'])
      ->orderBy('created_at', 'desc')
      ->get();

    foreach ($pendingOrders as $order) {
      try {
        $fireBlocks = FireBlocksController::getFireBlocks();

        $vaultAccountAsset = $fireBlocks->get_vault_account_asset(
          $order->address->vault_account_id,
          $order->address->asset_id);

        if ($order->after_amount != $vaultAccountAsset['available']) {
          $order->net_amount = $vaultAccountAsset['available'] - $order->after_amount;
          $order->after_amount = $vaultAccountAsset['available'];

          if ($order->net_amount == $order->depositOrder->amount) {
            $order->payment_status = Constants::$PAYMENT_STATUS['complete'];
          }

          if ($order->net_amount > $order->depositOrder->amount) {
            $order->payment_status = Constants::$PAYMENT_STATUS['over'];
          }

          if( $order->fee_amount == null || $order->fee_amount == 0 )
            $order->fee_amount = $order->depositOrder->amount * $order->depositOrder->partner->crypto_fee / 100;

          $order->save();
        }
      } catch (Exception $e) {
        $this->info($e->getMessage());
        Log::info($e->getMessage());
      }
    }

    $endTime = now();
    $duration = $endTime->diffInSeconds($startTime);

    $this->info("Scanned all address successfully in $duration s.");
    Log::info("Scanned all address successfully in $duration s.");

    $cronJobMonitor = FbCronJobMonitor::first();
    if( $cronJobMonitor ){
      $cronJobMonitor->duration = $duration;
    }
    else{
      $cronJobMonitor = new FbCronJobMonitor();
      $cronJobMonitor->duration = $duration;
    }
    $cronJobMonitor->save();

    return 0;
  }
}
