<?php
namespace App\Http\Controllers;
use App\FbDepositOrderAddress;
use App\Library\ApiKey;
use App\Library\Constants;
use App\Library\DateUtil;
use App\Partner;
use App\Transaction;
use http\Exception\RuntimeException;
use Illuminate\Http\Request;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Mockery\Exception;

require_once (app_path().'/includes/api/Payment.php');
require_once (app_path().'/includes/api/Inquiry.php');
require_once (app_path().'/includes/api/VoidRequest.php');
require_once (app_path().'/includes/api/Settlement.php');
require_once (app_path().'/includes/api/Refund.php');
require_once (app_path().'/includes/PHPGangsta/GoogleAuthenticator.php');


class PaymentController extends Controller
{
  private $STATUS_LIST = array(
    "PCPS"=>"Pre-stage",
    "I"=>"Initial",
    "A"=>"Approved",
    "V"=>"Voided",
    "S"=>"Settled",
    "R"=>"Refund",
    "P"=>"Pending Payment",
    "F"=>"Rejected",
    "E"=>"Expired",
    "C"=>"Cancelled",
  );

  private $KAISER_DOMAIN = 'API.KAISERPAYMENT.COM';

  public function prePayment(Request $request)
  {
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
          // Check request parameters
          $amount = $request->input("amount");
          $productName = $request->input("product_name");
          if(($amount == null || !is_numeric($amount)) && ($productName == null || strlen(trim($productName)) == 0 )){
            $code = 400;
            $message = "Payment Error 5";
            $body = "Empty request.";
          }
          else if($amount == null || !is_numeric($amount) || is_numeric($amount) < 0){
            $code = 400;
            $message = "Payment Error 6";
            $body = "Valid amount is required.";
          }
          else if($productName == null || strlen(trim($productName)) == 0){
            $code = 400;
            $message = "Payment Error 7";
            $body = "Product name is required.";
          }
          else{
            $payment = new \Payment();
            $response = $payment->ExecuteJose($amount, $productName, $apiKeyPartner['domain'], $apiKeyPartner['fee']);
            $respData = json_decode($response, true);
            if (is_array($respData) && isset($respData['data'])) {

              // Save transaction
              $transaction = new Transaction();
              $transaction->partner_id = $apiKeyPartner['id'];
              $transaction->email_address = null;
              $transaction->card_holder_name = null;
              $transaction->orderNo = $respData['data']['paymentIncompleteResult']['orderNo'];
              $transaction->amount = $amount;
              $transaction->fee = $amount * $apiKeyPartner['fee'] / 100;
              $transaction->fee_percent = $apiKeyPartner['fee'];
              $transaction->product_name = $productName;
              $transaction->status = $respData['data']['paymentIncompleteResult']['paymentStatusInfo']['paymentStatus'];
              $transaction->partner_domain = $apiKeyPartner['domain'];
              $transaction->save();

              $success = true;
              $body = $respData['data']['paymentPage']['paymentPageURL'];
              $message = "paymentPageURL";
            }
            else {
              $code = 500;
              $message = "Payment Error 8";
              $body = $response;
            }
          }
        }
      }
      else {
        $code = 403;
        $message = "Report Error 1: Authorization is missing.";
      }
    }
    catch (GuzzleException $e) {
      $code = 500;
      $message = "Report Error 9: ".$e->getMessage();
    }
    catch (\Exception $e) {
      $code = 500;
      $message = "Report Error 10: ".$e->getMessage();
    }
    return response()->json([
      'code' => $code,
      'success' => $success,
      'message' => $message,
      'timestamp' => $timestamp,
      'body' => $body
    ])->setStatusCode($code);
  }

  public function getReport(Request $request): JsonResponse
  {
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
          $orderNo = $request->input('orderNo');
          $name = $request->input('name');
          $fromDate = $request->input('fromDate');
          $toDate = $request->input('toDate');
          $email = $request->input('email');
          $productName = $request->input('productName');
          $status = $request->input('status');
          $pageSize = $request->input('pageSize', 10); // Default to 10 results per page
          $pageNo = $request->input('pageNo', 1); // Default to page 1

          // Build the query for searching orders
          $query = Transaction::query()
            ->leftJoin('partners', 'transactions.partner_id', '=', 'partners.id')
            ->select(
              'transactions.orderNo',
              'transactions.partner_id',
              'transactions.email_address',
              'transactions.card_holder_name',
              DB::raw('CAST(transactions.amount AS CHAR) as amount'),
              DB::raw('CAST(transactions.fee AS CHAR) as fee'),
              DB::raw('CAST(transactions.fee_percent AS CHAR) as fee_percent'),
              'transactions.product_name',
              'transactions.status',
              'transactions.partner_domain',
              'transactions.created_at',
              'transactions.updated_at',
              'transactions.deleted_at',
              'partners.name as name',
              'partners.domain')
            ->orderByDesc('transactions.created_at');

          // Kaiser can get all transactions
          if( $apiKeyPartner['domain'] != $this->KAISER_DOMAIN ){
            $query->where('partners.id', '=', $apiKeyPartner['id']);
          }

          if ( $orderNo ) {
            $query->where('transactions.orderNo', 'like', '%' . $orderNo . '%');
          }

          if ( $name ) {
            $query->where('partners.name', 'LIKE', '%' . $name . '%');
          }

          if ( $fromDate ) {
            $query->where('transactions.updated_at', '>=', $fromDate . ' 00:00:00');
          }

          if ( $toDate ) {
            $query->where('transactions.updated_at', '<=', $toDate . ' 23:59:59');
          }

          if ( $email ) {
            $query->where('transactions.email_address', 'like', '%' . $email . '%');
          }

          if ( $productName ) {
            $query->where('transactions.product_name', 'like', '%' . $productName . '%');
          }

          if ( $status ) {
            $query->where('transactions.status', '=', $status);
          }

          // Calculate total result count and page count
          if( $pageSize == 0 )
            $pageSize = 10;
          $totalResults = $query->count();
          $totalPages = ceil($totalResults / $pageSize);
          if( $dbPartner->domain == $this->KAISER_DOMAIN ){
            $totalRecords = Transaction::count();
          }
          else{
            $partnerId = $dbPartner->id;
            $totalRecords = Transaction::whereHas('partner', function ($query) use ($partnerId) {
              $query->where('id', $partnerId);
            })->count();
          }

          // Apply pagination
          $query->offset(($pageNo - 1) * $pageSize)
            ->limit($pageSize);

          // Fetch the results
          $results = $query->get();
          //echo $query->toSql();

          // update database from JDB
          $uncompletedOrderNo = [];
          foreach( $results as $result )
            $uncompletedOrderNo[] = $result->orderNo;

          $inquiry = new \Inquiry();
          $response = $inquiry->ExecuteWithOrderNos($uncompletedOrderNo);
          if( $response != null ){
            $respData = json_decode($response, true);
            if (is_array($respData) && isset($respData['data'])) {
              $jdbData = $respData['data'];
              foreach ($jdbData as $item){
                $updatedDate = (isset($item['paymentStatusInfo']) && isset($item['paymentStatusInfo']['lastUpdatedDttm'])) ? $item['paymentStatusInfo']['lastUpdatedDttm'] : null;
                $paymentStatus = (isset($item['paymentStatusInfo']) && isset($item['paymentStatusInfo']['paymentStatus'])) ? $item['paymentStatusInfo']['paymentStatus'] : null;
                $holderName = (isset($item['creditCardDetails']) && isset($item['creditCardDetails']['cardHolderName'])) ? $item['creditCardDetails']['cardHolderName'] : null;
                $email = (isset($item['generalPayerDetails']) && isset($item['generalPayerDetails']['email'])) ? $item['generalPayerDetails']['email'] : null;
                $orderNo = $item['orderNo'];
                $amount = $item['transactionAmount']['amount'];
                $transaction = Transaction::where('orderNo', $orderNo)->first();
                if( $transaction == null )
                  continue;

                // check transaction is 1 day ago.
                $stringDateTimestamp = strtotime($updatedDate);
                $timeDifference = now()->timestamp - $stringDateTimestamp;
                $daysDifference = $timeDifference / (60 * 60 * 24);
                if ($daysDifference >= 1 && $holderName == null)
                  $holderName = '';
                if ($daysDifference >= 1 && $email == null)
                  $email = '';

                // update local transaction
                $transaction->email_address = $email;
                $transaction->card_holder_name = $holderName;
                $transaction->status = $paymentStatus;
                $transaction->amount = $amount;
                $transaction->save();
              }
            }
          }

          // re-fetch data from database
          $results = $query->get();

          foreach ($results as $result){
            unset($result->partner_id);
            unset($result->deleted_at);
            unset($result->partner_domain);
            $result->status = $this->STATUS_LIST[$result->status];
            $result->created_at = DateUtil::convertToUTC($result->created_at);
            $result->updated_at = DateUtil::convertToUTC($result->updated_at);
          }

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

  private function isContainEmail($email, $item): bool
  {
    if( $email == null ){
      $result = true;
    }
    else if( strlen(trim($email)) == 0){
      $result = true;
    }
    else{
      if( $item["generalPayerDetails"] != null ){
        $pattern = "/$email/i";
        if (preg_match($pattern, $item["generalPayerDetails"]["email"]))
          $result = true;
        else
          $result = false;
      }
      else{
        $result = false;
      }
    }

    return $result;
  }

  private function isContainName($name, $item): bool
  {
    if( $name == null ){
      $result = true;
    }
    else if( strlen(trim($name)) == 0 ){
      return true;
    }
    else{
      if( $item["creditCardDetails"] != null && $item["creditCardDetails"]["cardHolderName"] != null){
        $pattern = "/$name/i";
        if (preg_match($pattern, $item["creditCardDetails"]["cardHolderName"]))
          $result = true;
        else
          $result = false;
      }
      else{
        $result = false;
      }
    }

    return $result;
  }

  private function getCustomFieldValue($item, $fieldName){
    foreach ($item["customFieldList"] as $field) {
      if ($field["fieldName"] === $fieldName) {
        return $field["fieldValue"];
      }
    }

    return null;
  }
}

