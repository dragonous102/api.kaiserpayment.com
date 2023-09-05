<?php
namespace App\Http\Controllers;
use App\Library\ApiKey;
use App\Library\DateUtil;
use App\Partner;
use App\Transaction;
use Illuminate\Http\Request;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\JsonResponse;

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

    try {
      if ($request->hasHeader('Authorization')) {
        $apiKey = $request->header('Authorization');
        $apiKeyPartner = ApiKey::parseJwtToken($apiKey);
        if(isset($apiKeyPartner['error'])){
          $code = 400;
          $message = "Payment Error 2";
          $body = "Invalid authorization API key.";
        }
        else{
          // Check api key validation
          $dbPartner = Partner::find($apiKeyPartner['id']);
          if( $dbPartner == null || $dbPartner->status == 0 ){
            $code = 400;
            $message = "Payment Error 3";
            $body = "This request to access the Kaiser API was declined.";
          }
          else{
            if( $dbPartner->id != $apiKeyPartner['id'] ||
              strtolower($dbPartner->name) != strtolower($apiKeyPartner['name']) ||
              $dbPartner->fee != $apiKeyPartner['fee'] ||
              strtolower($dbPartner->domain) != strtolower($apiKeyPartner['domain'])){
              $code = 400;
              $message = "Payment Error 4";
              $body = "Invalid authorization API key. It does not match the correct partner's information.";
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
                $response = $payment->ExecuteJose($amount, $productName, $apiKeyPartner['domain']);
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
        }
      }
      else {
        $code = 400;
        $message = "Payment Error 1";
        $body = "Authorization is missing.";
      }
    }
    catch (GuzzleException $e) {
      $code = 500;
      $message = "Payment Error 6";
      $body = $e->getMessage();
    }
    catch (\Exception $e) {
      $code = 500;
      $message = "Payment Error 7";
      $body = $e->getMessage();
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
    $body = array();

    try {
      if ($request->hasHeader('Authorization')) {
        $apiKey = $request->header('Authorization');
        $apiKeyPartner = ApiKey::parseJwtToken($apiKey);
        if(isset($apiKeyPartner['error'])){
          $code = 400;
          $message = "Report Error 2";
          $body = "Invalid authorization.";
        }
        else{
          // Check api key validation
          $dbPartner = Partner::find($apiKeyPartner['id']);
          if( $dbPartner == null || $dbPartner->status == 0 ){
            $code = 400;
            $message = "Payment Error 3";
            $body = "This request to access the Kaiser API was declined.";
          }
          else{
            if( $dbPartner->id != $apiKeyPartner['id'] ||
              strtolower($dbPartner->name) != strtolower($apiKeyPartner['name']) ||
              $dbPartner->fee != $apiKeyPartner['fee'] ||
              strtolower($dbPartner->domain) != strtolower($apiKeyPartner['domain'])){
              $code = 400;
              $message = "Payment Error 4";
              $body = "Invalid authorization API key. It does not match the correct partner's information.";
            }
            else{
              // Check uncompleted transactions and update local DB with JDB transactions
              $uncompletedOrderNo = [];
              $transactions = Transaction::whereNull('card_holder_name')->get();

              foreach( $transactions as $transaction )
                $uncompletedOrderNo[] = $transaction->orderNo;

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

              // search transactions in local DB
              $query = Transaction::query();

              // Kaiser can get all transactions
              if( $apiKeyPartner['domain'] != $this->KAISER_DOMAIN ){
                $query->where('partners.domain', '=', $apiKeyPartner['domain']);
              }

              if (isset($request->orderNo) && $request->orderNo != null) {
                $query->where('transactions.orderNo', 'like', '%' . $request->orderNo . '%');
              }

              if (isset($request->fromDate) && $request->fromDate != null ) {
                $query->where('transactions.updated_at', '>=', $request->fromDate . ' 00:00:00');
              }

              if (isset($request->toDate) && $request->toDate != null ) {
                $query->where('transactions.updated_at', '<=', $request->toDate . ' 23:59:59');
              }

              if (isset($request->email) && $request->email != null ) {
                $query->where('transactions.email_address', 'like', '%' . $request->email . '%');
              }

              if (isset($request->name) && $request->name != null ) {
                $query->where('partners.name', 'like', '%' . $request->name . '%');
              }

              if (isset($request->productName) && $request->productName != null ) {
                $query->where('transactions.product_name', 'like', '%' . $request->productName . '%');
              }

              if (isset($request->status) && $request->status != null ) {
                $query->where('transactions.status', '=', $request->status);
              }

              if (isset($request->maxResults) && $request->maxResults != null ) {
                $query->limit($request->maxResults);
              }

              $query->join('partners', 'transactions.partner_id', '=', 'partners.id')
                ->select(
                  'transactions.*',
                  'partners.name as name',
                  'partners.domain'
                );
              $query->orderBy('transactions.created_at', 'desc');
              //echo $query->toSql();
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
              $body = $results;
            }
          }
        }
      }
      else {
        $code = 400;
        $message = "Report Error 1";
        $body = "Authorization is missing.";
      }
    }
    catch (GuzzleException $e) {
      $success = true;
      $message = "report data";
    }
    catch (\Exception $e) {
      $success = true;
      $message = "report data";
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

