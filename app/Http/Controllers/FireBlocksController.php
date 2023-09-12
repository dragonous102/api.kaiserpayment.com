<?php

namespace App\Http\Controllers;

use App\FbAddress;
use App\FbDepositOrder;
use App\FbDepositOrderAddress;
use App\Library\ApiKey;
use App\Library\Constants;
use App\Partner;
use FireblocksSdkPhp\Exceptions\FireblocksApiException;
use GuzzleHttp\Exception\GuzzleException;
use http\Exception\RuntimeException;
use Illuminate\Http\Request;
use FireblocksSdkPhp\FireblocksSDK;
use Mockery\Exception;

class FireBlocksController extends Controller
{
  public function showGetAddressPage(Request $request): string
  {
    return view('pages.fireblocks_getAddress');
  }

  public function getNewDepositAddress(Request $request){
    $code = 200;
    $success = false;
    $timestamp = now()->toIso8601String();
    $body = [];

    try {
      if ($request->hasHeader('Authorization')) {

        // Check API key
        try {
          $apiKey = $request->header('Authorization');
          $apiKeyPartner = ApiKey::parseJwtToken($apiKey);
          $dbPartner = Partner::find($apiKeyPartner['id']);
          $apiKeyStatus = ApiKey::isValidApiKey($dbPartner, $apiKeyPartner);
        }
        catch (Exception $e){
          $code = 403;
          $message = "Invalid authorization API key.";
          throw new RuntimeException($message, $code);
        }
        if( $apiKeyStatus == 'UN_REGISTERED_API_KEY'){
          $code = 403;
          $message = "Payment Error 2: Invalid authorization API key. It is not registered authorization API key.";
        }
        elseif( $apiKeyStatus == 'NOT_MATCHED_API_KEY'){
          $code = 403;
          $message = "Payment Error 3: Invalid authorization API key. It does not match the correct partner's information.";
        }
        else{
          // Check request params
          $assetId = $request->input("currency");
          $productName = $request->input("productName");
          $amount = $request->input("amount");

          if(($amount == null || !is_numeric($amount)) &&
            ($productName == null || strlen(trim($productName)) == 0 ) &&
            ($assetId == null || strlen(trim($assetId)) == 0 )){
            $code = 400;
            $message = "Payment Error 4: Empty request.";
          }
          else if($amount == null || !is_numeric($amount) || is_numeric($amount) < 0){
            $code = 400;
            $message = "Payment Error 5: Valid amount is required.";
          }
          else if($productName == null || strlen(trim($productName)) == 0){
            $code = 400;
            $message = "Payment Error 6: Product name is required.";
          }
          else if($assetId == null || strlen(trim($assetId)) == 0){
            $code = 400;
            $message = "Payment Error 7: Currency is required.";
          }
          else{
            // Save order
            $randomString = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 8);
            $timestamp = time();
            $orderNo = $randomString . sprintf("%012d", $timestamp);

            $fbOrder = new FbDepositOrder();
            $fbOrder->partner_id = $dbPartner->id;
            $fbOrder->order_id = $orderNo;
            $fbOrder->amount = $amount;
            $fbOrder->product_name = $productName;
            $fbOrder->currency = $assetId;
            $fbOrder->save();

            // Use fireBlocks SDK
            try {
              $fireBlocks = $this->getFireBlocks();

              // make new vault account name
              $vaultAccountName = sprintf("kaiser_%s_%s", $assetId, $orderNo);

              // create new vault account
              $response = $fireBlocks->create_vault_account($vaultAccountName);
              $vaultAccountId = $response['id'];
              $fireBlocks->create_vault_asset($vaultAccountId, $assetId);

              // create new deposit address
              $response = $fireBlocks->get_deposit_addresses($vaultAccountId, $assetId);

              if( $response != null ){

                // save address
                $fbAddress = new FbAddress();
                $fbAddress->address = $response[0]['address'];
                $fbAddress->legacy_address = $response[0]['legacyAddress'];
                $fbAddress->asset_id = $assetId;
                $fbAddress->vault_account_id = $vaultAccountId;
                $fbAddress->vault_account_name = $vaultAccountName;
                $fbAddress->save();

                // save order-address
                $fbOrderAddress = new FbDepositOrderAddress();
                $fbOrderAddress->deposit_order_id = $fbOrder->id;
                $fbOrderAddress->address_id = $fbAddress->id;
                $fbOrderAddress->fee_amount = $amount * $dbPartner->crypto_fee / 100;
                $fbOrderAddress->prev_amount = 0;
                $fbOrderAddress->after_amount = 0;
                $fbOrderAddress->net_amount = 0;
                $fbOrderAddress->payment_status = Constants::$PAYMENT_STATUS['pending'];
                $fbOrderAddress->description = Constants::$CREATED_BY['system'];
                $fbOrderAddress->action_status = Constants::$ACTION_STATUS['success'];
                $fbOrderAddress->save();

                $success = true;
                $message = "$fbOrder->currency deposit address for $fbOrder->product_name.";
                $body['order_id'] = $fbOrder->order_id;
                $body['currency'] = $fbOrder->currency;
                $body['address'] = $fbAddress->address;
                $body['status'] = $fbOrderAddress->action_status;
              }
              else{
                // save order-address
                $fbOrderAddress = new FbDepositOrderAddress();
                $fbOrderAddress->deposit_order_id = $fbOrder->id;
                $fbOrderAddress->fee_amount = $amount * $dbPartner->crypto_fee / 100;
                $fbOrderAddress->prev_amount = 0;
                $fbOrderAddress->after_amount = 0;
                $fbOrderAddress->net_amount = 0;
                $fbOrderAddress->payment_status = Constants::$PAYMENT_STATUS['pending'];
                $fbOrderAddress->description = Constants::$CREATED_BY['system'];
                $fbOrderAddress->action_status = Constants::$ACTION_STATUS['failed'];
                $fbOrderAddress->save();

                $message = "Payment Error 8: Failed to get $assetId deposit address.";
                $code = 400;
                $body['order_id'] = $fbOrder->order_id;
                $body['currency'] = $fbOrder->currency;
                $body['address'] = null;
                $body['status'] = $fbOrderAddress->action_status;
                $body['reason'] = "Failed to get $assetId deposit address.";
              }
            }
            catch (\Exception $e){
              // save order-address
              $fbOrderAddress = new FbDepositOrderAddress();
              $fbOrderAddress->deposit_order_id = $fbOrder->id;
              $fbOrderAddress->fee_amount = $amount * $dbPartner->crypto_fee / 100;
              $fbOrderAddress->prev_amount = 0;
              $fbOrderAddress->after_amount = 0;
              $fbOrderAddress->net_amount = 0;
              $fbOrderAddress->payment_status = Constants::$PAYMENT_STATUS['pending'];
              $fbOrderAddress->description = Constants::$CREATED_BY['system'];
              $fbOrderAddress->action_status = Constants::$ACTION_STATUS['failed'];
              $fbOrderAddress->save();

              $message = "Payment Error 9: Failed to get $assetId deposit address.";
              $code = $e->getCode();
              $body['order_id'] = $fbOrder->order_id;
              $body['currency'] = $fbOrder->currency;
              $body['address'] = null;
              $body['status'] = $fbOrderAddress->action_status;
              $body['reason'] = $e->getMessage();
            }
          }
        }
      }
      else {
        $code = 403;
        $message = "Payment Error 1: Authorization is missing.";
      }
    }
    catch (GuzzleException $e) {
      $code = $e->getCode();
      $message = "Payment Error 10: ".$e->getMessage();
    }
    catch (\Exception $e) {
      $code = $e->getCode();
      $message = "Payment Error 11: ".$e->getMessage();
    }
    if( $code == 0 )
      $code = 400;
    return response()->json([
      'code' => $code,
      'success' => $success,
      'message' => $message,
      'timestamp' => $timestamp,
      'body' => $body
    ])->setStatusCode($code);
  }

  public function showReportPage(Request $request): string
  {
    return view('pages.fireblocks_getReport');
  }

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
