<?php

namespace App\Http\Controllers;

use FireblocksSdkPhp\Exceptions\FireblocksApiException;
use FireblocksSdkPhp\Types\DestinationTransferPeerPath;
use FireblocksSdkPhp\Types\Enums\PeerEnums;
use FireblocksSdkPhp\Types\TransferPeerPath;
use Illuminate\Http\Request;
//use Hub\FireBlocksSdk\AccountService;
use FireblocksSdkPhp\FireblocksSDK;
use Mockery\Exception;

class FireBlocksController extends Controller
{
  public function showTestPage(Request $request){
    return view('pages.fireblocks');
  }

  private function getFireBlocks(): FireblocksSDK
  {
    $relativePath = config('fireblocks.private_key_file');
    $absolutePath = base_path($relativePath);
    $private_key = file_get_contents($absolutePath);
    $api_key = config('fireblocks.api_key');
    return new FireblocksSDK($private_key, $api_key);
  }

  /**
   * @throws FireblocksApiException
   */
  public function getAccount(Request $request){
    $code = 200;
    $success = false;
    $timestamp = now()->toIso8601String();
    $body = [];

    try {
      $fireBlocks = $this->getFireBlocks();
      //$result = $fireBlocks->get_gas_station_info();

      // Create new vault account with name
      //$result = $fireBlocks->create_vault_account("kaiser_USDT1");//34250
      $result = $fireBlocks->get_vault_accounts("kaiser");
      //$result = $fireBlocks->get_supported_assets();

      // Update vault account's name
      //$result = $fireBlocks->update_vault_account("34250", "kaiser_BTC1");

      // Create BTC wallet with asset
      //$result = $fireBlocks->create_vault_asset("34198", "BTC");
      //$result = $fireBlocks->create_vault_asset("34199", "USDT");
      //$result = $fireBlocks->create_vault_asset("34199", "BTC_TEST");// not work
      //$result = $fireBlocks->get_users();// not work
      //$result = $fireBlocks->activate_vault_asset("31499", "USDT");
      //$result = $fireBlocks->create_vault_asset("34199", "USDT");
      //$result = $fireBlocks->create_vault_asset("34198", "BUSD");
      //$fireBlocks->create_vault_asset("34254", "USDT");
      //$result = $fireBlocks->get_deposit_addresses("34254", "USDT");
      //$result = $fireBlocks->get_network_connections();


      //$result = $fireBlocks->get_vault_assets_balance("kaiser_BTC");


      /*$dest = [
        'address' => '0x72e0595064DaF9D3B07CF6Eb6D1B7D3E9bd31CBE'
      ];

      // Encode the destination array as a JSON string
      $destJson = json_encode($dest);
      $result = $fireBlocks->create_transaction(
        "BNB_BSC",
        0.001,
        new TransferPeerPath(PeerEnums::VAULT_ACCOUNT(), "34198"),
        new DestinationTransferPeerPath(PeerEnums::ONE_TIME_ADDRESS(), null, $dest, 0.0005, null, null, null, null, 0.0005)
      );*/

      //$result = $fireBlocks->get_transactions(0, 0, null, 10, 'lastUpdated');
      //$result = $fireBlocks->set_confirmation_threshold_for_txid("9e8ef1ca-7bea-4857-a9fc-90c6d3b378ce", 1);


      //$result = $fireBlocks->get_vault_balance_by_asset("BTC");
      //$result = $fireBlocks->get_contract_wallets();// not work


      //$result = $fireBlocks->get_vault_accounts();

      // Get BTC wallets
      //$result = $fireBlocks->get_vault_account_asset("34198", "BTC");
      if( $result != null ){
        $success = true;
        $message = "All of the vault accounts.";
        $body = $result;
      }
      else{
        $message = "Failed to get the vault accounts.";
        $code = 400;
      }
    }
    catch (Exception $e){
      $code = 500;
      $message = $e->getMessage();
    }

    return response()->json([
      'code' => $code,
      'success' => $success,
      'message' => $message,
      'timestamp' => $timestamp,
      'body' => $body,
    ])->setStatusCode($code);
  }

  public function getNewDepositAddress(Request $request){
    $code = 200;
    $success = false;
    $timestamp = now()->toIso8601String();
    $body = [];

    try {

      // use fireBlocks SDK
      $fireBlocks = $this->getFireBlocks();

      // make new vault account name
      $timestamp = time();
      $orderNo = sprintf("%010d", $timestamp);
      $assetId = $request->input("currency");
      $vaultAccountName = sprintf("kaiser_%s_%s", $assetId, $orderNo);

      // create new vault account
      $response = $fireBlocks->create_vault_account($vaultAccountName);
      $vaultAccountId = $response['id'];
      $fireBlocks->create_vault_asset($vaultAccountId, $assetId);

      // create new deposit address
      $response = $fireBlocks->get_deposit_addresses($vaultAccountId, $assetId);

      if( $response != null ){
        $success = true;
        $message = "New $assetId deposit address.";
        $response[0]['accountName'] = $vaultAccountName;
        $body = $response;
      }
      else{
        $message = "Failed to get new $assetId deposit address.";
        $code = 400;
      }
    } catch (\Exception $e) {
      $code = 500;
      $message = $e->getMessage();
    }

    return response()->json([
      'code' => $code,
      'success' => $success,
      'message' => $message,
      'timestamp' => $timestamp,
      'body' => $body,
    ])->setStatusCode($code);
  }

  public function getAccountBalance(Request $request){
    $code = 200;
    $success = false;
    $timestamp = now()->toIso8601String();
    $body = [];

    try {
      // use fireBlocks SDK
      $fireBlocks = $this->getFireBlocks();

      // make new vault account name
      $accountName = $request->input("accountName");

      // create new vault account
      $response = $fireBlocks->get_vault_assets_balance($accountName);

      if( $response != null ){
        $success = true;
        $message = "The balance of $accountName.";
        $body = $response;
      }
      else{
        $message = "Failed to get balance of $accountName.";
        $code = 400;
      }
    } catch (\Exception $e) {
      $code = 500;
      $message = $e->getMessage();
    }

    return response()->json([
      'code' => $code,
      'success' => $success,
      'message' => $message,
      'timestamp' => $timestamp,
      'body' => $body,
    ])->setStatusCode($code);
  }

  public function getSupportedAssets(Request $request){
  $code = 200;
  $success = false;
  $timestamp = now()->toIso8601String();
  $body = [];

  try {
    // use fireBlocks SDK
    $fireBlocks = $this->getFireBlocks();

    // create new vault account
    $response = $fireBlocks->get_supported_assets();

    if( $response != null ){
      $success = true;
      $message = "List of supported assets";
      $body = $response;
    }
    else{
      $message = "Failed to get supported assets";
      $code = 400;
    }
  } catch (\Exception $e) {
    $code = 500;
    $message = $e->getMessage();
  }

  return response()->json([
    'code' => $code,
    'success' => $success,
    'message' => $message,
    'timestamp' => $timestamp,
    'body' => $body,
  ])->setStatusCode($code);
}
}
