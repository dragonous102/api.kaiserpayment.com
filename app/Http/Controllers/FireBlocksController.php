<?php

namespace App\Http\Controllers;

use App\FbAddress;
use App\FbCronJobMonitor;
use App\FbDepositOrder;
use App\FbDepositOrderAddress;
use App\Library\ApiKey;
use App\Library\Constants;
use App\Partner;
use Carbon\Carbon;
use GuzzleHttp\Exception\GuzzleException;
use http\Exception\RuntimeException;
use Illuminate\Http\Request;
use FireblocksSdkPhp\FireblocksSDK;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Mockery\Exception;

class FireBlocksController extends Controller
{
  private $KAISER_DOMAIN = 'API.KAISERPAYMENT.COM';

  public function showGetAddressPage(Request $request): string
  {
    return view('pages.fireblocks_getAddress');
  }

  public function getCryptoPaymentAddress(Request $request){
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
        elseif( $dbPartner == null || $dbPartner->status == Constants::$PARTNER_STATUS['disabled'] ){
          $code = 403;
          $message = "Payment Error 4: Invalid authorization API key. This request to access the Kaiser API was declined.";
        }
        else{
          // Check request params
          $assetId = $request->input("currency");
          $productName = $request->input("productName");
          $amount = $request->input("amount");
          $email = $request->input("email");
          $name = $request->input("name");

          if(($amount == null || !is_numeric($amount)) &&
            ($productName == null || strlen(trim($productName)) == 0 ) &&
            ($assetId == null || strlen(trim($assetId)) == 0 ) &&
            ($email == null || strlen(trim($email)) == 0 ) &&
            ($name == null || strlen(trim($name)) == 0 )){
            $code = 400;
            $message = "Payment Error 5: Empty request.";
          }
          else if($amount == null || !is_numeric($amount) || is_numeric($amount) < 0){
            $code = 400;
            $message = "Payment Error 6: Valid amount is required.";
          }
          else if($productName == null || strlen(trim($productName)) == 0){
            $code = 400;
            $message = "Payment Error 7: Product name is required.";
          }
          else if($assetId == null || strlen(trim($assetId)) == 0){
            $code = 400;
            $message = "Payment Error 8: Currency is required.";
          }
          else if($email == null || strlen(trim($email)) == 0){
            $code = 400;
            $message = "Payment Error 9: Email is required.";
          }
          else if($name == null || strlen(trim($name)) == 0){
            $code = 400;
            $message = "Payment Error 10: Name is required.";
          }
          else{
            // Save order
            $randomString = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 7);
            $now = Carbon::now();
            $timestamp = $now->getPreciseTimestamp(3);
            $orderNo = $randomString . sprintf("%013d", $timestamp);

            $fbOrder = new FbDepositOrder();
            $fbOrder->partner_id = $dbPartner->id;
            $fbOrder->order_id = $orderNo;
            $fbOrder->amount = $amount;
            $fbOrder->product_name = $productName;
            $fbOrder->currency = $assetId;
            $fbOrder->email = $email;
            $fbOrder->name = $name;
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
                $body['email'] = $fbOrder->email;
                $body['name'] = $fbOrder->name;
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
                $fbOrderAddress->payment_status = Constants::$PAYMENT_STATUS['failed'];
                $fbOrderAddress->description = Constants::$CREATED_BY['system'];
                $fbOrderAddress->action_status = Constants::$ACTION_STATUS['failed'];
                $fbOrderAddress->save();

                $message = "Payment Error 11: Failed to get $assetId deposit address.";
                $code = 400;
                $body['order_id'] = $fbOrder->order_id;
                $body['currency'] = $fbOrder->currency;
                $body['address'] = null;
                $body['email'] = $fbOrder->email;
                $body['name'] = $fbOrder->name;
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
              $fbOrderAddress->payment_status = Constants::$PAYMENT_STATUS['failed'];
              $fbOrderAddress->description = Constants::$CREATED_BY['system'];
              $fbOrderAddress->action_status = Constants::$ACTION_STATUS['failed'];
              $fbOrderAddress->save();

              $message = "Payment Error 12: Failed to get $assetId deposit address.";
              $code = $e->getCode();
              $body['order_id'] = $fbOrder->order_id;
              $body['currency'] = $fbOrder->currency;
              $body['address'] = null;
              $body['status'] = $fbOrderAddress->action_status;
              $body['email'] = $fbOrder->email;
              $body['name'] = $fbOrder->name;
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
      $code = 500;
      $message = "Payment Error 13: ".$e->getMessage();
    }
    catch (\Exception $e) {
      $code = 500;
      $message = "Payment Error 14: ".$e->getMessage();
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

  public function getCryptoPaymentReport(Request $request){
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
          $message = "Report Error 2: Invalid authorization API key. It is not registered authorization API key.";
        }
        elseif( $apiKeyStatus == 'NOT_MATCHED_API_KEY'){
          $code = 403;
          $message = "Report Error 3: Invalid authorization API key. It does not match the correct partner's information.";
        }
        elseif( $dbPartner == null || $dbPartner->status == Constants::$PARTNER_STATUS['disabled'] ){
          $code = 403;
          $message = "Report Error 4: Invalid authorization API key. This request to access the Kaiser API was declined.";
        }
        else{
          // Extract search parameters from the request
          $orderId = $request->input('orderId');
          $partnerName = $request->input('partner');
          $fromDate = $request->input('fromDate');
          $toDate = $request->input('toDate');
          $address = $request->input('address');
          $paymentStatus = $request->input('paymentStatus');
          $pageSize = $request->input('pageSize', 10); // Default to 10 results per page
          $pageNo = $request->input('pageNo', 1); // Default to page 1

          // Build the query for searching orders
          $query = FbDepositOrderAddress::query()
            ->leftJoin('fb_deposit_order', 'fb_deposit_order.id', '=', 'fb_deposit_order_address.deposit_order_id')
            ->leftJoin('fb_addresses', 'fb_addresses.id', '=', 'fb_deposit_order_address.address_id')
            ->leftJoin('partners', 'fb_deposit_order.partner_id', '=', 'partners.id')
            ->select(
              'fb_deposit_order.order_id',
              'partners.name as partner_name',
              'fb_deposit_order.currency as currency',
              'fb_deposit_order.name',
              'fb_deposit_order.email',
              DB::raw('CAST(fb_deposit_order.amount AS CHAR) as payment_amount'),
              'fb_addresses.address',
              DB::raw('CAST(fb_deposit_order_address.net_amount AS CHAR) as wallet_balance'),
              'fb_deposit_order_address.payment_status as payment_status',
              DB::raw('CAST(fb_deposit_order_address.fee_amount AS CHAR) as fee'),
              'fb_deposit_order_address.action_status as status',
              'fb_deposit_order_address.updated_at'
            )
            ->orderByDesc('fb_deposit_order_address.updated_at');

          // Apply filters based on search parameters
          if ($orderId) {
            $query->where('fb_deposit_order.order_id', 'LIKE', '%' . $orderId . '%');
          }

          // Kaiser can get all transactions
          if( $dbPartner->domain != $this->KAISER_DOMAIN ){
            $query->where('fb_deposit_order.partner_id', $dbPartner->id);
          }

          if ($partnerName) {
            $query->where('partners.name', 'LIKE', '%' . $partnerName . '%');
          }

          if ($fromDate) {
            $query->whereDate('fb_deposit_order_address.updated_at', '>=', $fromDate.' 00:00:00');
          }

          if ($toDate) {
            $query->whereDate('fb_deposit_order_address.updated_at', '<=', $toDate.' 23:59:59');
          }

          if ($address) {
            $query->where('fb_addresses.address', 'LIKE', '%' . $address . '%');
          }

          if ($paymentStatus) {
            $query->where('fb_deposit_order_address.payment_status', $paymentStatus);
          }

          // Calculate total result count and page count
          if( $pageSize == 0 )
            $pageSize = 10;
          $totalResults = $query->count();
          $totalPages = ceil($totalResults / $pageSize);
          if( $dbPartner->domain == $this->KAISER_DOMAIN ){
            $totalRecords = FbDepositOrderAddress::count();
          }
          else{
            $partnerId = $dbPartner->id;
            $totalRecords = FbDepositOrderAddress::whereHas('depositOrder.partner', function ($query) use ($partnerId) {
              $query->where('id', $partnerId);
            })->count();
          }

          // Apply pagination
          $query->offset(($pageNo - 1) * $pageSize)
            ->limit($pageSize);

          // Fetch the results
          $results = $query->get();

          $success = true;
          $message = "report data";
          $body['data'] = $results;
          $body['page_count'] = $totalPages;
          $body['page_no'] = $pageNo == null ? 1 : $pageNo;
          $body['page_size'] = $pageSize;
          $body['total_data_size'] = $totalRecords;
          $body['searched_data_size'] = $totalResults;
        }
      }
      else {
        $code = 403;
        $message = "Report Error 1: Authorization is missing.";
      }
    }
    catch (GuzzleException $e) {
      $code = 500;
      $message = "Report Error 5: ".$e->getMessage();
    }
    catch (\Exception $e) {
      $code = 500;
      $message = "Report Error 6: ".$e->getMessage();
    }
    return response()->json([
      'code' => $code,
      'success' => $success,
      'message' => $message,
      'timestamp' => $timestamp,
      'body' => $body
    ])->setStatusCode($code);
  }

  public function webhook(Request $request){
    Log::info("webhook received request data:");
    Log::info(json_encode($request));

    // Get the JSON payload from the request body
    $jsonPayload = $request->getContent();

    // Decode the JSON payload into an associative array
    $webhookData = json_decode($jsonPayload, true);
    Log::info("webhookData:");
    Log::info(json_encode($webhookData));

    // Check if decoding was successful
    if ($webhookData === null) {
      Log::info("responses with 400");
      return response()->json([
        'error' => 'Invalid JSON data'
      ])->setStatusCode(400);
    }

    Log::info("responses with 200");
    return response()->json([
      'message' => 'ok'
    ])->setStatusCode(200);
  }

  public function showCronJobPage(Request $request): string
  {
    $cronJobMonitor = FbCronJobMonitor::first();
    $lastExecutedTime = 'Never';
    $executionDuration = 0;
    $cronJobStatus = 'stopped';

    if ($cronJobMonitor) {
      // last executed time
      $lastExecutedTime = $cronJobMonitor->updated_at;
      $lastExecutedTimeCarbon = Carbon::parse($cronJobMonitor->updated_at);
      $timeDifferenceMinutes = $lastExecutedTimeCarbon->diffInMinutes(Carbon::now());
      $lastExecutedTime .= '( '.$timeDifferenceMinutes.' minutes ago)';

      // execute duration
      $executionDuration = $cronJobMonitor->duration;

      // running status
      $currentTime = Carbon::now();
      $timeDifferenceMinutes = $currentTime->diffInMinutes($cronJobMonitor->updated_at);
      if ($timeDifferenceMinutes < 5) {
        $cronJobStatus = 'running';
      }
    }

    // Pass the data to the view
    return View::make('pages.fireblocks_cronJob', [
      'lastExecutedTime' => $lastExecutedTime,
      'executionDuration' => $executionDuration,
      'cronJobStatus' => $cronJobStatus,
    ]);
  }

  public function showTestPage(Request $request){
    return view('pages.fireblocks');
  }

  public static function getFireBlocks(): FireblocksSDK
  {
    $relativePath = config('fireblocks.private_key_path');
    $absolutePath = base_path($relativePath);
    $private_key = file_get_contents($absolutePath);
    $api_key = config('fireblocks.api_key');
    return new FireblocksSDK($private_key, $api_key);
  }

  /**
   */
  public function getAccount(Request $request){
    $code = 200;
    $success = false;
    $timestamp = now()->toIso8601String();
    $body = [];

    try {

      $fireBlocks = $this->getFireBlocks();
      //$result = $fireBlocks->get_vault_accounts("kaiser");
      //$result = $fireBlocks->get_vault_account_asset("34198", "USDT_BSC");
      $result = $fireBlocks->get_deposit_addresses("34794", "USDT_ERC20");
      //$result = $fireBlocks->resend_webhooks();
      //$result = $fireBlocks->get_transactions(0, 0, null, 100, 'lastUpdated');
      //$result = $fireBlocks->resend_transaction_webhooks_by_id('a9b12d91-d3f8-4cf8-9d8c-9b174d52bbdd', true, false);
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

      // get account id
      $address = $request->input("address");
      $vaultAccountName = FbAddress::where('address', $address)->value('vault_account_name');

      // get account balance
      if($vaultAccountName)
        $response = $fireBlocks->get_vault_assets_balance($vaultAccountName);
      else
        $response = null;

      if( $response != null ){
        $success = true;
        $message = "The balance of $vaultAccountName.";
        $body = $response;
      }
      else{
        $message = "Failed to get balance of $vaultAccountName.";
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
