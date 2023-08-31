<?php

use Carbon\Carbon;
use GuzzleHttp\Exception\GuzzleException;
use Webpatser\Uuid\Uuid;
use App\includes\ActionRequest;
use App\includes\SecurityData;

class Payment extends ActionRequest
{
  /**
   * @throws Exception
   */
  /**
   * @throws GuzzleException
   */
  public function Execute($amount = 0 ): string
  {
    $crypto_amount = $amount * 0.94;
    $crypto_amount = bcdiv($crypto_amount, 1, 6);
    $amount = ceil($amount * 100) / 100;
    $amount_text = ceil($amount * 100);
    $len = strlen((string)$amount_text);
    for ($i=0; $i < (12 - $len); $i++) {
      # code...
      $amount_text = "0".$amount_text;

    }
    $now = Carbon::now();
    $orderNo = $now->getPreciseTimestamp(3);
    $app_url = env("APP_URL");
    $request = [
      "apiRequest" => [
        "requestMessageID" => Uuid::generate()->string,
        "requestDateTime" => $now->utc()->format("Y-m-d\TH:i:s.v\Z"),
        "language" => "en-US"
      ],
      "officeId" => "000002105010108",
      "orderNo" => $orderNo,
      "productDescription" => "For buying {$crypto_amount} USDT",
      "paymentType" => "CC",
      "paymentCategory" => "ECOM",
      "storeCardDetails" => [
        "storeCardFlag" => "N",
        "storedCardUniqueID" => Uuid::generate()->string
      ],
      "installmentPaymentDetails" => [
        "ippFlag" => "N",
        "installmentPeriod" => 0,
        "interestType" => null
      ],
      "mcpFlag" => "N",
      "request3dsFlag" => "N",
      "transactionAmount" => [
        "amountText" => $amount_text,
        "currencyCode" => "USD",
        "decimalPlaces" => 2,
        "amount" => $amount
      ],
      "notificationURLs" => [
        "confirmationURL" => "{$app_url}/payment-confirmation",
        "failedURL" => "{$app_url}/payment-failed",
        "cancellationURL" => "{$app_url}/payment-cancellation",
        "backendURL" => "{$app_url}/payment-backend"
      ],

      "customFieldList" => [
        [
          "fieldName" => "TestField",
          "fieldValue" => "This is test"
        ]
      ]
    ];
    $stringRequest = json_encode($request);
    //third-party http client https://github.com/guzzle/guzzle
    $response = $this->client->post("api/1.0/Payment/prePaymentUI", [
      "headers" => [
        "Accept" => "application/json",
        "apiKey" => SecurityData::$AccessToken,
        "Content-Type" => "application/json; charset=utf-8"
      ],
      "body" => $stringRequest
    ]);

    return $response->getBody()->getContents();
  }
  public function ExecuteNonUI($amount): string
  {
    $now = Carbon::now();
    $orderNo = $now->getPreciseTimestamp(3);
    $app_url = env("APP_URL");
    $request = [
      "apiRequest" => [
        "requestMessageID" => Uuid::generate()->string,
        "requestDateTime" => $now->utc()->format("Y-m-d\TH:i:s.v\Z"),
        "language" => "en-US"
      ],
      "officeId" => "000002105010090",
      "orderNo" => $orderNo,
      "productDescription" => "desc for " . $orderNo,
      "paymentType" => "CC",
      "paymentCategory" => "ECOM",
      "creditCardDetails" => [
        "cardNumber" => "6225830010000202",
        "cardExpiryMMYY" => "1030",
        "cvvCode" => "683",
        "payerName" => "Auttachai"
      ],
      "storeCardDetails" => [
        "storeCardFlag" => "N",
        "storedCardUniqueID" => Uuid::generate()->string
      ],
      "installmentPaymentDetails" => [
        "ippFlag" => "N",
        "installmentPeriod" => 0,
        "interestType" => null
      ],
      "mcpFlag" => "N",
      "request3dsFlag" => "N",
      "transactionAmount" => [
        "amountText" => "000000{$amount}00",
        "currencyCode" => "USD",
        "decimalPlaces" => 2,
        "amount" => $amount
      ],
      "notificationURLs" => [
        "confirmationURL" => "{$app_url}/payment-confirmation",
        "failedURL" => "{$app_url}/payment-failed",
        "cancellationURL" => "{$app_url}/payment-cancellation",
        "backendURL" => "{$app_url}/payment-backend"
      ],
      "deviceDetails" => [
        "browserIp" => "1.0.0.1",
        "browser" => "Postman Browser",
        "browserUserAgent" => "PostmanRuntime/7.26.8 - not from header",
        "mobileDeviceFlag" => "N"
      ],
      "purchaseItems" => [
        [
          "purchaseItemType" => "ticket",
          "referenceNo" => "2322460376026",
          "purchaseItemDescription" => "Bundled insurance",
          "purchaseItemPrice" => [
            "amountText" => "000000100000",
            "currencyCode" => "THB",
            "decimalPlaces" => 2,
            "amount" => 1000
          ],
          "subMerchantID" => "string",
          "passengerSeqNo" => 1
        ]
      ],
      "customFieldList" => [
        [
          "fieldName" => "TestField",
          "fieldValue" => "This is test"
        ]
      ]
    ];

    $stringRequest = json_encode($request);

    //third-party http client https://github.com/guzzle/guzzle
    $response = $this->client->post("api/1.0/Payment/nonUI", [
      "headers" => [
        "Accept" => "application/json",
        "apiKey" => SecurityData::$AccessToken,
        "Content-Type" => "application/json; charset=utf-8"
      ],
      "body" => $stringRequest
    ]);

    return $response->getBody()->getContents();
  }

  /**
   * @throws GuzzleException
   * @throws Exception
   */
  public function ExecuteJose($amount = 0, $productName = "", $host = ""): string
  {
    // Prepare basic information
    $amount = ceil($amount * 100) / 100;
    $fee = $amount * 8 / 100;
    $amount_text = ceil($amount * 100);
    $len = strlen((string)$amount_text);
    for ($i=0; $i < (12 - $len); $i++) {
      $amount_text = "0".$amount_text;
    }
    $now = Carbon::now();
    $orderNo = $now->getPreciseTimestamp(3);

    // Prepare urls
    if( $host == "localhost" )
      $baseUrl = "http://localhost:8000";
    else
      $baseUrl = "https://".$host;

    $request = [
      "apiRequest" => [
        "requestMessageID" => Uuid::generate()->string,
        "requestDateTime" => $now->utc()->format("Y-m-d\TH:i:s.v\Z"),
        "language" => "en-US"
      ],
      "officeId" => "000002105010090",
      "orderNo" => $orderNo,
      "productDescription" => $productName,
      "paymentType" => "CC",
      "paymentCategory" => "ECOM",
      "mcpFlag" => "N",
      "request3dsFlag" => "N",
      "transactionAmount" => [
        "amountText" => $amount_text,
        "currencyCode" => "USD",
        "decimalPlaces" => 2,
        "amount" => $amount
      ],
      "notificationURLs" => [
        "confirmationURL" => "{$baseUrl}/payment-confirmation",
        "failedURL" => "{$baseUrl}/payment-failed",
        "cancellationURL" => "{$baseUrl}/payment-cancellation",
        "backendURL" => "{$baseUrl}/payment-backend"
      ],
      "customFieldList" => [
        [
          "fieldName" => "fee",
          "fieldValue" => $fee
        ],
        [
          "fieldName" => "partner",
          "fieldValue" => $host
        ]
      ]
    ];

    $url = ActionRequest::getPaymentEndpoint()."api/1.0/Payment/prePaymentUI";
    $headers = [
      "Content-Type: application/json; charset=utf-8",
      "apiKey: ".SecurityData::$AccessToken,
    ];
    $body = json_encode($request);
    $options = [
      'http' => [
        'header'  => implode("\r\n", $headers),
        'method'  => 'POST',
        'content' => $body,
      ],
    ];
    $context = stream_context_create($options);
    return file_get_contents($url, false, $context);
  }

  public function ExecuteJoseNonUI(): string
  {
    $now = Carbon::now();
    $orderNo = $now->getPreciseTimestamp(3);

    $request = [
      "apiRequest" => [
        "requestMessageID" => Uuid::generate()->string,
        "requestDateTime" => $now->utc()->format("Y-m-d\TH:i:s.v\Z"),
        "language" => "en-US",
      ],
      "officeId" => "000002105010090",
      "orderNo" => $orderNo,
      "productDescription" => "desc for {$orderNo}",
      "paymentType" => "CC",
      "paymentCategory" => "ECOM",
      "creditCardDetails" => [
        "cardNumber" => "4706860000002325",
        "cardExpiryMMYY" => "1225",
        "cvvCode" => "761",
        "payerName" => "Demo Sample"
      ],
      "storeCardDetails" => [
        "storeCardFlag" => "N",
        "storedCardUniqueID" => Uuid::generate()->string
      ],
      "installmentPaymentDetails" => [
        "ippFlag" => "N",
        "installmentPeriod" => 0,
        "interestType" => null
      ],
      "mcpFlag" => "N",
      "request3dsFlag" => "N",
      "transactionAmount" => [
        "amountText" => "000000100000",
        "currencyCode" => "THB",
        "decimalPlaces" => 2,
        "amount" => 1000
      ],
      "notificationURLs" => [
        "confirmationURL" => "http://example-confirmation.com",
        "failedURL" => "http://example-failed.com",
        "cancellationURL" => "http://example-cancellation.com",
        "backendURL" => "http://example-backend.com"
      ],
      "deviceDetails" => [
        "browserIp" => "1.0.0.1",
        "browser" => "Postman Browser",
        "browserUserAgent" => "PostmanRuntime/7.26.8 - not from header",
        "mobileDeviceFlag" => "N"
      ],
      "purchaseItems" => [
        [
          "purchaseItemType" => "ticket",
          "referenceNo" => "2322460376026",
          "purchaseItemDescription" => "Bundled insurance",
          "purchaseItemPrice" => [
            "amountText" => "000000100000",
            "currencyCode" => "THB",
            "decimalPlaces" => 2,
            "amount" => 1000
          ],
          "subMerchantID" => "string",
          "passengerSeqNo" => 1
        ]
      ],
      "customFieldList" => [
        [
          "fieldName" => "TestField",
          "fieldValue" => "This is test"
        ]
      ]
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
    $response = $this->client->post("api/1.0/Payment/NonUi", [
      "headers" => [
        "Accept" => "application/jose",
        "CompanyApiKey" => SecurityData::$AccessToken,
        "Content-Type" => "application/jose; charset=utf-8"
      ],
      "body" => $body
    ]);

    $token = $response->getBody()->getContents();
    $decryptingKey = $this->GetPrivateKey(SecurityData::$MerchantDecryptionPrivateKey);
    $signatureVerificationKey = $this->GetPublicKey(SecurityData::$PacoSigningPublicKey);

    return $this->DecryptToken($token, $decryptingKey, $signatureVerificationKey);
  }
}
