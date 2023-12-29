<?php
namespace App\Http\Controllers;
use App\Library\ApiKey;
use App\Library\Constants;
use App\Library\DateUtil;
use App\Mail\SendEmail;
use App\Partner;
use App\Transaction;
use ErrorException;
use GuzzleHttp\Exception\GuzzleException;
use http\Exception\RuntimeException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Mockery\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use Swift_TransportException;

require_once (app_path().'/includes/JDB_UAT/api/Payment.php');
require_once (app_path().'/includes/JDB_UAT/api/Inquiry.php');
require_once (app_path().'/includes/JDB_UAT/api/VoidRequest.php');
require_once (app_path().'/includes/JDB_UAT/api/Settlement.php');
require_once (app_path().'/includes/JDB_UAT/api/Refund.php');

require_once (app_path().'/includes/JDB_PROD/api/Payment.php');
require_once (app_path().'/includes/JDB_PROD/api/Inquiry.php');
require_once (app_path().'/includes/JDB_PROD/api/VoidRequest.php');
require_once (app_path().'/includes/JDB_PROD/api/Settlement.php');
require_once (app_path().'/includes/JDB_PROD/api/Refund.php');


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
          $email = $request->input("email");
          $name = $request->input("name");

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
            $apiKeyPartner = ApiKey::parseJwtToken($apiKey);
            $serviceType = $apiKeyPartner['service_type'];
            if( $serviceType == Constants::$UAT || $serviceType == Constants::$PROD ){

              if( $serviceType == Constants::$UAT ) {
                $payment = new \App\includes\JDB_UAT\api\Payment();
                $response = $payment->ExecuteJose($amount, $productName, $apiKeyPartner['domain'], $apiKeyPartner['fee'], $email, $name);
                $respData = json_decode($response, true);
                if (is_array($respData) && isset($respData['data'])) {

                  // Save transaction
                  $transaction = new Transaction();
                  $transaction->partner_id = $apiKeyPartner['id'];
                  $transaction->email_address = $email;
                  $transaction->username = $name;
                  $transaction->email_sent = 'draft';
                  $transaction->card_holder_name = null;
                  $transaction->orderNo = $respData['data']['paymentIncompleteResult']['orderNo'];
                  $transaction->amount = $amount;
                  $transaction->fee = $amount * $apiKeyPartner['fee'] / 100;
                  $transaction->fee_percent = $apiKeyPartner['fee'];
                  $transaction->product_name = $productName;
                  $transaction->status = $respData['data']['paymentIncompleteResult']['paymentStatusInfo']['paymentStatus'];
                  $transaction->partner_domain = $apiKeyPartner['domain'];
                  $transaction->service_type = $apiKeyPartner['service_type'];
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
              else {
                $payment = new \App\includes\JDB_PROD\api\Payment();
                $response = $payment->ExecuteJose($amount, $productName, $apiKeyPartner['domain'], $apiKeyPartner['fee'], $email, $name);
                $respData = json_decode($response, true);

                if (is_array($respData) && isset($respData['response']) && isset($respData['response']['Data'])) {

                  // Save transaction
                  $transaction = new Transaction();
                  $transaction->partner_id = $apiKeyPartner['id'];
                  $transaction->email_address = $email;
                  $transaction->username = $name;
                  $transaction->email_sent = 'draft';
                  $transaction->card_holder_name = null;
                  $transaction->orderNo = $respData['response']['Data']['paymentIncompleteResult']['orderNo'];
                  $transaction->amount = $amount;
                  $transaction->fee = $amount * $apiKeyPartner['fee'] / 100;
                  $transaction->fee_percent = $apiKeyPartner['fee'];
                  $transaction->product_name = $productName;
                  $transaction->status = $respData['response']['Data']['paymentIncompleteResult']['paymentStatusInfo']['PaymentStatus'];
                  $transaction->partner_domain = $apiKeyPartner['domain'];
                  $transaction->service_type = $apiKeyPartner['service_type'];
                  $transaction->save();

                  $success = true;
                  $body = $respData['response']['Data']['paymentPage']['paymentPageURL'];
                  $message = "paymentPageURL";
                }
                else {
                  $code = 500;
                  $message = "Payment Error 8";
                  $body = $response;
                }
              }
            }
            else {
              $code = 400;
              $message = "Invalid service type.";
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

  public function getReport(Request $request)
  {
    $code = 200;
    $success = false;
    $timestamp = now()->toIso8601String();
    $body = [];
    $detailedData = null;

    // Extract search parameters from the request
    $detail = $request->input('detail');
    $orderNo = $request->input('orderNo');
    $name = $request->input('name');
    $fromDate = $request->input('fromDate');
    $toDate = $request->input('toDate');
    $email = $request->input('email');
    $productName = $request->input('productName');
    $status = $request->input('status');
    $pageSize = $request->input('pageSize', 10); // Default to 10 results per page
    $pageNo = $request->input('pageNo', 1); // Default to page 1
    $totalRecords = 0;
    $totalResults = 0;

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

          $query->whereNotNull('transactions.service_type');

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
          //echo $query->toSql();
          $totalResults = $query->count();
          $totalPages = ceil($totalResults / $pageSize);
          if( $dbPartner->domain == $this->KAISER_DOMAIN ){
            $totalRecords = Transaction::whereNotNull('service_type')->count();
          }
          else{
            $partnerId = $dbPartner->id;
            $totalRecords = Transaction::whereHas('partner', function ($query) use ($partnerId) {
              $query->where('id', $partnerId);
            })->whereNotNull('service_type')->count();
          }

          // Apply pagination
          $query->offset(($pageNo - 1) * $pageSize)
            ->limit($pageSize);

          // Fetch the results
          $results = $query->get();

          // update database from JDB
          $uncompletedOrderNo_UAT = [];
          $uncompletedOrderNo_PROD = [];
          foreach( $results as $result ) {

            $dbPartner = Partner::find($result['partner_id']);
            $serviceType = $dbPartner['service_type'];
            if( $serviceType == 'UAT' )
              $uncompletedOrderNo_UAT[] = $result->orderNo;
            else if( $serviceType == 'PROD' )
              $uncompletedOrderNo_PROD[] = $result->orderNo;
          }

          // get transaction from JDB UAT
          if( count($uncompletedOrderNo_UAT) > 0 ){
            $inquiry = new \App\includes\JDB_UAT\api\Inquiry();
            $response = $inquiry->ExecuteWithOrderNos($uncompletedOrderNo_UAT);
            if( $response != null ){
              $respData = json_decode($response, true);
              if (is_array($respData) && isset($respData['data'])) {
                $jdbData = $respData['data'];
                if( $detail == 1 )
                  $detailedData = $jdbData;
                foreach ($jdbData as $item){
                  $updatedDate = (isset($item['paymentStatusInfo']) && isset($item['paymentStatusInfo']['lastUpdatedDttm'])) ? $item['paymentStatusInfo']['lastUpdatedDttm'] : null;
                  $paymentStatus = (isset($item['paymentStatusInfo']) && isset($item['paymentStatusInfo']['paymentStatus'])) ? $item['paymentStatusInfo']['paymentStatus'] : null;
                  $holderName = (isset($item['creditCardDetails']) && isset($item['creditCardDetails']['cardHolderName'])) ? $item['creditCardDetails']['cardHolderName'] : null;
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

                  // update local transaction
                  $transaction->card_holder_name = $holderName;
                  $transaction->status = $paymentStatus;
                  $transaction->amount = $amount;
                  $transaction->save();
                }
              }
            }
          }

          // get transaction from JDB PROD;
          if( count($uncompletedOrderNo_PROD) > 0 ){
            $inquiry = new \App\includes\JDB_PROD\api\Inquiry();
            $response = null;
            try {
              $response = $inquiry->ExecuteWithOrderNos($uncompletedOrderNo_PROD);
            }
            catch (GuzzleException $e){
              echo 'exception';
            }
            if( $response != null ){
              $respData = json_decode($response, true);
              if (is_array($respData) && isset($respData['response']['Data'])) {
                $jdbData = $respData['response']['Data'];
                if( $detail == 1 )
                  $detailedData = $jdbData;
                foreach ($jdbData as $item){
                  $updatedDate = (isset($item['PaymentStatusInfo']) && isset($item['PaymentStatusInfo']['LastUpdatedDttm'])) ? $item['PaymentStatusInfo']['LastUpdatedDttm'] : null;
                  $paymentStatus = (isset($item['PaymentStatusInfo']) && isset($item['PaymentStatusInfo']['PaymentStatus'])) ? $item['PaymentStatusInfo']['PaymentStatus'] : null;
                  $holderName = (isset($item['CreditCardDetails']) && isset($item['CreditCardDetails']['CardHolderName'])) ? $item['CreditCardDetails']['CardHolderName'] : null;
                  $orderNo = $item['OrderNo'];
                  $amount = $item['TransactionAmount']['Amount'];
                  $transaction = Transaction::where('orderNo', $orderNo)->first();
                  if( $transaction == null )
                    continue;

                  // check transaction is 1 day ago.
                  $stringDateTimestamp = strtotime($updatedDate);
                  $timeDifference = now()->timestamp - $stringDateTimestamp;
                  $daysDifference = $timeDifference / (60 * 60 * 24);
                  if ($daysDifference >= 1 && $holderName == null)
                    $holderName = '';

                  // update local transaction
                  $transaction->card_holder_name = $holderName;
                  $transaction->status = $paymentStatus;
                  $transaction->amount = $amount;
                  $transaction->save();
                }
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
          $body['detail'] = $detailedData;
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
      $message = "Report Error 6: ".$e->getTraceAsString();
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

  /**
   * @throws \PHPMailer\PHPMailer\Exception
   */
  public function kaiserPaymentConfirmation(Request $request)
  {
    // Check parameters
    $orderNo = $request->input('orderNo');
    $productDescription = $request->input('productDescription');
    $controllerInternalId = $request->input('controllerInternalId');

    // Create api request
    $apiRequest = Request::create(env('KAISER_BACKEND').'/api/getReport', 'GET', ['orderNo' => $orderNo, 'detail' => 1]);
    $apiRequest->headers->set('Authorization', env('API.KAISERPAYMENT.COM'));

    // Get service type
    $transaction = Transaction::where('orderNo', $orderNo)->first();
    $dbPartner = Partner::find($transaction->partner_id);
    $serviceType = $dbPartner->service_type;

    // Get a report
    $report = json_decode($this->getReport( $apiRequest )->getContent());
    $paymentStatus = $transaction->status;
    $cardHolderName = $transaction->card_holder_name;
    $email = $transaction->email_address;
    if( strtoupper($serviceType) == 'UAT' ) {
      $paymentStatus = $report->body->detail[0]->paymentStatusInfo->paymentStatus;
      $cardHolderName = $report->body->detail[0]->creditCardDetails->cardHolderName;
    }
$emailError = 'no error';
    // Create and send email
    if( $email != null && strlen($email) > 0 ) {
      /*try {
        $mailingResult = Mail::to($email)->send(new SendEmail('success'));
        $transaction->email_sent = 'sent';
        Log::info('Email sent successfully');
        Log::info(json_encode($mailingResult));
      } catch (Swift_TransportException $transportException) {
        $transaction->email_sent = 'failed';
        Log::error('SMTP Exception: ' . $transportException->getMessage());
        $emailError = $transportException->getMessage();
      } catch (Exception $exception) {
        $transaction->email_sent = 'failed';
        Log::error('Exception: ' . $exception->getMessage());
        $emailError = $exception->getMessage();
      }*/

      /*$emailObj = new PHPMailer(true);
      $emailObj->isSMTP();
      $emailObj->SMTPDebug = 2;
      $emailObj->SMTPAuth = true;
      $emailObj->SMTPAutoTLS = false; // Disable auto TLS
      $emailObj->SMTPSecure = "ssl";
      $emailObj->Host = env('MAIL_HOST');
      $emailObj->Port = env('MAIL_PORT');
      $emailObj->Username = env('MAIL_USERNAME');
      $emailObj->Password = env('MAIL_PASSWORD');

      $emailObj->setFrom(env('MAIL_FROM_ADDRESS'));
      $emailObj->addAddress($email);
      $emailObj->isHTML(true);
      $emailObj->Body = "My HTML Code";
      $emailObj->Subject = "My Subject";

      try {
        $emailObj->send();
      } catch (Exception $e) {
        $emailError = $emailObj->ErrorInfo;
      }*/

      $rawtext ="Verification code for buying ";
      Mail::send([], [], function ($message) use ($rawtext, $email) {
        $message->to($email)->subject('Please confirm your buy.');
        $message->from(env("MAIL_FROM_ADDRESS"));
        $message->setBody($rawtext, 'text/html');
      });
    }

    // Save payment to a report
    $transaction->status = $paymentStatus;
    $transaction->card_holder_name = $cardHolderName;
    $transaction->save();
    return $emailError;

    // Redirect to partner's backend
    $redirectUrl = sprintf('https://%s/payment-confirmation?orderNo=%s&productDescription=%s&controllerInternalId=%s',
      strtolower($dbPartner->domain),
      $orderNo,
      $productDescription,
      $controllerInternalId);
    return redirect($redirectUrl);
  }

  public function kaiserPaymentFailed(Request $request)
  {
    // Check parameters
    $orderNo = $request->input('orderNo');
    $productDescription = $request->input('productDescription');
    $controllerInternalId = $request->input('controllerInternalId');

    // Create api request
    $apiRequest = Request::create(env('KAISER_BACKEND').'/api/getReport', 'GET', ['orderNo' => $orderNo, 'detail' => 1]);
    $apiRequest->headers->set('Authorization', env('API.KAISERPAYMENT.COM'));

    // Get service type
    $transaction = Transaction::where('orderNo', $orderNo)->first();
    $dbPartner = Partner::find($transaction->partner_id);
    $serviceType = $dbPartner->service_type;

    // Get a report
    $report = json_decode($this->getReport( $apiRequest )->getContent());
    $paymentStatus = $transaction->status;
    $cardHolderName = $transaction->card_holder_name;
    $email = $transaction->email_address;
    if( strtoupper($serviceType) == 'UAT' ) {
      $paymentStatus = $report->body->detail[0]->paymentStatusInfo->paymentStatus;
      $cardHolderName = $report->body->detail[0]->creditCardDetails->cardHolderName;
    }

    // Create and send email
    if( $email != null && strlen($email) > 0 ) {
      try {
        $mailingResult = Mail::to($email)->send(new SendEmail('failure'));
        $transaction->email_sent = 'sent';
        Log::info('Email sent successfully');
        Log::info(json_encode($mailingResult));
      } catch (Swift_TransportException $transportException) {
        $transaction->email_sent = 'failed';
        Log::error('SMTP Exception: ' . $transportException->getMessage());
      } catch (Exception $exception) {
        $transaction->email_sent = 'failed';
        Log::error('Exception: ' . $exception->getMessage());
      }
    }

    // Save payment to a report
    $transaction->status = $paymentStatus;
    $transaction->card_holder_name = $cardHolderName;
    $transaction->save();

    // Redirect to partner's backend
    $redirectUrl = sprintf('https://%s/payment-failed?orderNo=%s&productDescription=%s&controllerInternalId=%s',
      strtolower($dbPartner->domain),
      $orderNo,
      $productDescription,
      $controllerInternalId);
    return redirect($redirectUrl);
  }

  public function kaiserPaymentCancellation(Request $request)
  {
    // Check parameters
    $orderNo = $request->input('orderNo');
    $productDescription = $request->input('productDescription');
    $controllerInternalId = $request->input('controllerInternalId');

    // Create api request
    $apiRequest = Request::create(env('KAISER_BACKEND').'/api/getReport', 'GET', ['orderNo' => $orderNo, 'detail' => 1]);
    $apiRequest->headers->set('Authorization', env('API.KAISERPAYMENT.COM'));

    // Get service type
    $transaction = Transaction::where('orderNo', $orderNo)->first();
    $dbPartner = Partner::find($transaction->partner_id);
    $serviceType = $dbPartner->service_type;

    // Get a report
    $report = json_decode($this->getReport( $apiRequest )->getContent());
    $paymentStatus = $transaction->status;
    $cardHolderName = $transaction->card_holder_name;
    $email = $transaction->email_address;
    if( strtoupper($serviceType) == 'UAT' ) {
      try {
        $paymentStatus = $report->body->detail[0]->paymentStatusInfo->paymentStatus;
        $cardHolderName = $report->body->detail[0]->creditCardDetails->cardHolderName;
      }
      catch (ErrorException $e){}
    }

    // Create and send email
    if( $email != null && strlen($email) > 0 ) {
      try {
        $mailingResult = Mail::to($email)->send(new SendEmail('cancel'));
        $transaction->email_sent = 'sent';
        Log::info('Email sent successfully');
        Log::info(json_encode($mailingResult));
      } catch (Swift_TransportException $transportException) {
        $transaction->email_sent = 'failed';
        Log::error('SMTP Exception: ' . $transportException->getMessage());
      } catch (Exception $exception) {
        $transaction->email_sent = 'failed';
        Log::error('Exception: ' . $exception->getMessage());
      }
    }

    // Save payment to a report
    $transaction->status = $paymentStatus;
    $transaction->card_holder_name = $cardHolderName;
    $transaction->save();

    // Redirect to partner's backend
    $redirectUrl = sprintf('https://%s/payment-cancellation?orderNo=%s&productDescription=%s&controllerInternalId=%s',
      strtolower($dbPartner->domain),
      $orderNo,
      $productDescription,
      $controllerInternalId);
    return redirect($redirectUrl);
  }

  public function kaiserPaymentBackend(Request $request)
  {
    // Check parameters
    $orderNo = $request->input('orderNo');
    $productDescription = $request->input('productDescription');
    $controllerInternalId = $request->input('controllerInternalId');

    // Create api request
    $apiRequest = Request::create(env('KAISER_BACKEND').'/api/getReport', 'GET', ['orderNo' => $orderNo, 'detail' => 1]);
    $apiRequest->headers->set('Authorization', env('API.KAISERPAYMENT.COM'));

    // Get service type
    $transaction = Transaction::where('orderNo', $orderNo)->first();
    $dbPartner = Partner::find($transaction->partner_id);

    // Redirect to partner's backend
    $redirectUrl = sprintf('https://%s/payment-backend?orderNo=%s&productDescription=%s&controllerInternalId=%s',
      strtolower($dbPartner->domain),
      $orderNo,
      $productDescription,
      $controllerInternalId);
    return redirect($redirectUrl);
  }
}

