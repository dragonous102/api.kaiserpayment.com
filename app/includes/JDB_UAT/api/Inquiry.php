<?php

namespace App\includes\JDB_UAT\api;

use App\includes\JDB_UAT\ActionRequest;
use App\includes\JDB_UAT\SecurityData;
use Carbon\Carbon;
use DateTime;
use Exception;
use Webpatser\Uuid\Uuid;

class Inquiry extends ActionRequest
{
  /**
   * @throws Exception
   */
  public function Execute(): string
  {
    $now = Carbon::now();

    $officeId = "DEMOOFFICE";
    $orderNo = "1635476979216";

    $request = [
      "apiRequest" => [
        "requestMessageID" => Uuid::generate()->string,
        "requestDateTime" => $now->utc()->format('Y-m-d\TH:i:s.v\Z'),
        "language" => "en-US",
      ],
      "advSearchParams" => [
        "controllerInternalID" => null,
        "officeId" => [
          $officeId
        ],
        "orderNo" => [
          "$orderNo"
        ],
        "invoiceNo2C2P" => null,
        "fromDate" => "0001-01-01T00:00:00",
        "toDate" => "0001-01-01T00:00:00",
        "amountFrom" => null,
        "amountTo" => null
      ],
    ];

    $stringRequest = json_encode($request);

    //third-party http client https://github.com/guzzle/guzzle
    $response = $this->client->post('api/1.0/Inquiry/transactionList', [
      'headers' => [
        'Accept' => 'application/json',
        'apiKey' => SecurityData::$AccessToken,
        'Content-Type' => 'application/json; charset=utf-8'
      ],
      'body' => $stringRequest
    ]);

    return $response->getBody()->getContents();
  }

  /**
   * @throws Exception
   */
  public function ExecuteWithParam($orderNo, $fromDate, $toDate, $status): string
  {
    // orderNo
    if ($orderNo == null || strlen(trim($orderNo)) == 0)
      $orderNo = null;

    // fromDate
    if ($fromDate != null && strlen(trim($fromDate)) > 0) {
      $date = new DateTime($fromDate);
      $fromDate = $date->format('Y-m-d\TH:i:s');
    } else {
      $fromDate = null;
    }

    // toDate
    if ($toDate != null && strlen(trim($toDate)) > 0) {
      $date = new DateTime($toDate);
      $toDate = $date->format('Y-m-d\T') . '23:59:59';
    } else {
      $toDate = null;
    }

    // status
    if ($status == null || strlen(trim($status)) == 0)
      $status = null;

    $now = Carbon::now();

    $request = [
      "apiRequest" => [
        "requestMessageID" => Uuid::generate()->string,
        "requestDateTime" => $now->utc()->format('Y-m-d\TH:i:s.v\Z'),
        "language" => "en-US",
      ],
      "advSearchParams" => [
        "officeId" => [
          "000002105010090"
        ],
      ],
    ];

    if ($orderNo != null)
      $request["advSearchParams"]["orderNo"] = [$orderNo];
    if ($fromDate != null)
      $request["advSearchParams"]["fromDate"] = $fromDate;
    if ($toDate != null)
      $request["advSearchParams"]["toDate"] = $toDate;
    if ($status != null)
      $request["advSearchParams"]["paymentStatus"] = [$status];

    $stringRequest = json_encode($request);

    //return $stringRequest;

    $response = $this->client->post('api/1.0/Inquiry/transactionList', [
      'headers' => [
        'Accept' => 'application/json',
        'apiKey' => SecurityData::$AccessToken,
        'Content-Type' => 'application/json; charset=utf-8'
      ],
      'body' => $stringRequest
    ]);

    return $response->getBody()->getContents();
  }

  /**
   * @throws Exception
   */
  public function ExecuteJose(): string
  {
    $now = Carbon::now();

    $officeId = "DEMOOFFICE";
    $orderNo = "1635476979216";

    $request = [
      "apiRequest" => [
        "requestMessageID" => Uuid::generate()->string,
        "requestDateTime" => $now->utc()->format('Y-m-d\TH:i:s.v\Z'),
        "language" => "en-US",
      ],
      "advSearchParams" => [
        "controllerInternalID" => null,
        "officeId" => [
          $officeId
        ],
        "orderNo" => [
          $orderNo
        ],
        "invoiceNo2C2P" => null,
        "fromDate" => "0001-01-01T00:00:00",
        "toDate" => "0001-01-01T00:00:00",
        "amountFrom" => null,
        "amountTo" => null
      ],
    ];

    $payload = [
      "request" => $request,
      "iss" => SecurityData::$AccessToken,
      "aud" => "PacoAudience",
      "CompanyApiKey" => SecurityData::$AccessToken,
      "iat" => $now->unix(),
      "nbf" => $now->unix(),
      "exp" => $now->addHour()->unix(),
    ];

    $stringPayload = json_encode($payload);
    $signingKey = $this->GetPrivateKey(SecurityData::$MerchantSigningPrivateKey);
    $encryptingKey = $this->GetPublicKey(SecurityData::$PacoEncryptionPublicKey);

    $body = $this->EncryptPayload($stringPayload, $signingKey, $encryptingKey);

    //third-party http client https://github.com/guzzle/guzzle
    $response = $this->client->post('api/1.0/Inquiry/transactionList', [
      'headers' => [
        'Accept' => 'application/jose',
        'CompanyApiKey' => SecurityData::$AccessToken,
        'Content-Type' => 'application/jose; charset=utf-8'
      ],
      'body' => $body
    ]);

    $token = $response->getBody()->getContents();
    $decryptingKey = $this->GetPrivateKey(SecurityData::$MerchantDecryptionPrivateKey);
    $signatureVerificationKey = $this->GetPublicKey(SecurityData::$PacoSigningPublicKey);

    return $this->DecryptToken($token, $decryptingKey, $signatureVerificationKey);
  }

  /**
   * @throws Exception
   */
  public function ExecuteWithOrderNos(array $uncompletedOrderNo): ?string
  {
    if (count($uncompletedOrderNo) == 0)
      return null;

    $now = Carbon::now();

    $request = [
      "apiRequest" => [
        "requestMessageID" => Uuid::generate()->string,
        "requestDateTime" => $now->utc()->format('Y-m-d\TH:i:s.v\Z'),
        "language" => "en-US",
      ],
      "advSearchParams" => [
        "officeId" => [
          "000002105010090"
        ],
        "orderNo" => $uncompletedOrderNo,
      ],
    ];

    $stringRequest = json_encode($request);

    $response = $this->client->post('api/1.0/Inquiry/transactionList', [
      'headers' => [
        'Accept' => 'application/json',
        'apiKey' => SecurityData::$AccessToken,
        'Content-Type' => 'application/json; charset=utf-8'
      ],
      'body' => $stringRequest
    ]);

    return $response->getBody()->getContents();
  }
}
