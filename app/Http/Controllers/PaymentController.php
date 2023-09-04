<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
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

  public function prePayment(Request $request)
  {
    $code = 200;
    $success = false;
    $timestamp = now()->toIso8601String();

    try {
      $host = $request->getHost();
      $acceptedHosts = Config::get('hosts');
      if (in_array($host, $acceptedHosts)) {

        $amount = $request->input("amount");
        $productName = $request->input("product_name");
        if(($amount == null || !is_numeric($amount)) && ($productName == null || strlen(trim($productName)) == 0 )){
          $code = 400;
          $message = "Payment Error 1";
          $body = "Empty request.";
        }
        else if($amount == null || !is_numeric($amount)){
          $code = 400;
          $message = "Payment Error 2";
          $body = "Amount is required.";
        }
        else if($productName == null || strlen(trim($productName)) == 0){
          $code = 400;
          $message = "Payment Error 3";
          $body = "Product name is required.";
        }
        else{
          $payment = new \Payment();
          $response = $payment->ExecuteJose($amount, $productName, $host);
          $respData = json_decode($response, true);
          if (is_array($respData) && isset($respData['data'])) {
            $success = true;
            $body = $respData['data']['paymentPage']['paymentPageURL'];
            $message = "paymentPageURL";
          }
          else {
            $code = 500;
            $message = "Payment Error 4";
            $body = $response;
          }
        }
      }
      else {
        $code = 400;
        $message = "Payment Error 5";
        $body = "Can not accept this request.";
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

    $headers = $request->header();

    // You can then iterate through the headers
    foreach ($headers as $key => $value) {
      echo json_encode($key).":".json_encode($value)."<br>";
    }



    return response()->json([
      'code' => $code,
      'success' => $success,
        'message' => $message.' from '.$_SERVER['REMOTE_ADDR'].','.gethostbyaddr($_SERVER['REMOTE_ADDR']),
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

    $totalCount = 0;

    try {
      $host = $request->getHost();
      $acceptedHosts = Config::get('hosts');

      if (in_array($host, $acceptedHosts)) {

        $orderNo = $request->input("orderNo");
        $fromDate = $request->input("fromDate");
        $toDate = $request->input("toDate");
        $email = $request->input("email");
        $name = $request->input("name");
        $status = $request->input("status");
        $maxResults = $request->input("maxResults");

        if( $email == null || strlen(trim($email)) == 0 )
          $email = null;
        if( $name == null || strlen(trim($name)) == 0 )
          $name = null;
        if( $maxResults == null || strlen(trim($maxResults)) == 0 || !is_numeric($maxResults) )
          $maxResults = 0;

        $inquiry = new \Inquiry();
        $response = $inquiry->ExecuteWithParam($orderNo, $fromDate, $toDate, $status, $maxResults);
        $respData = json_decode($response, true);

        if (is_array($respData) && isset($respData['data'])) {
          $success = true;
          $message = "report data";
          $jdbData = $respData['data'];
          foreach ($jdbData as $item){
            if( $this->isContainEmail($email, $item) && $this->isContainName($name, $item)){
              $partnerHost = $this->getCustomFieldValue($item, "partner");
              $fullName = null;
              if($item["creditCardDetails"] != null && $item["creditCardDetails"]["cardHolderName"] != null )
                $fullName = $item["creditCardDetails"]["cardHolderName"];
              $emailBody = null;
              if($item["generalPayerDetails"] != null && $item["generalPayerDetails"]["email"] != null )
                $emailBody = $item["generalPayerDetails"]["email"];
              $amount = $item["transactionAmount"]["amount"];
              $fee = $this->getCustomFieldValue($item, "fee");
              if( $fee == null )
                $fee = 0;
              $amount -= $fee;
              if( $host != $partnerHost )
                continue;

              $body[] = array(
                "orderNo"=>$item["orderNo"],
                "name"=>$fullName,
                "email"=>$emailBody,
                "date"=>$item["transactionDateTime"],
                "amount"=>$amount,
                "fee"=>$fee,
                "status"=>$this->STATUS_LIST[$item["paymentStatusInfo"]["paymentStatus"]],
                "partner"=>$this->getCustomFieldValue($item, "partner")
              );
              $totalCount++;
              if( $maxResults > 0 && $maxResults == $totalCount )
                break;
            }
          }
        }
        else {
          $code = 500;
          $message = 'Report Error 1: ' . $response;
        }
      }
      else {
        $code = 500;
        $message = 'Report Error 2: Can not accept payment request.';
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

    // You can then iterate through the headers
    foreach ($_SERVER as $key => $value) {
      echo json_encode($key).":".json_encode($value)."<br>";
    }

    echo '<br/>'.json_encode(parse_url($_SERVER['HTTP_HOST']));
    echo '<br/>'.json_encode(parse_url($_SERVER['SERVER_NAME']));

    $proxy = (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : false;

    if(!!$proxy){
      $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
      echo "Warning: Your cliend is using proxy, may could not determine hostname";
    }else{
      $ipaddress = $_SERVER['REMOTE_ADDR']; //
    }
    $hostname = gethostbyaddr($ipaddress); //Its will return domain + machine-name inside a private network.

    if($ipaddress  == $hostname){
      echo "Impossible to determine hostname for: ", $ipaddress ;
    }else{
      echo "The hostname for ", $ipaddress, "is : ",  $hostname;
    }

    return response()->json([
      'code' => $code,
      'success' => $success,
      'message' => $message.' from '.$_SERVER['REMOTE_ADDR'].','.gethostbyaddr($_SERVER['REMOTE_ADDR']),
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

