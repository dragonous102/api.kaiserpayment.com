<?php
namespace App\includes\JDB_PROD\api;

use Carbon\Carbon;
use Exception;
use App\includes\JDB_PROD\ActionRequest;
use App\includes\JDB_PROD\SecurityData;

class Settlement extends ActionRequest
{
    /**
     */
    public function Execute(): string
    {
        $officeId = "DEMOOFFICE";
        $orderNo = "1643362945100"; //OrderNo can be Refund/Void one time
        $productDescription = "Sample request for 1643362945100";

        $request = [
            "officeId" => $officeId,
            "orderNo" => $orderNo,
            "productDescription" => $productDescription,
            "issuerApprovalCode" => "141857", // approvalCode of order place (Payment api) response
            "actionBy" => "System",
            "settlementAmount" => [
                "amountText" => "000000100000",
                "currencyCode" => "THB",
                "decimalPlaces" => 2,
                "amount" => 1000.00
            ],
        ];

        $stringRequest = json_encode($request);
        $response = $this->client->put('api/1.0/Settlement', [
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
        $orderNo = "1643362945100";
        $productDescription = "Sample request for 1643362945100";

        $request = [
            "officeId" => $officeId,
            "orderNo" => $orderNo,
            "productDescription" => $productDescription,
            "issuerApprovalCode" => "141857", // approvalCode of order place (Payment api) response
            "actionBy" => "System",
            "settlementAmount" => [
                "amountText" => "000000100000",
                "currencyCode" => "THB",
                "decimalPlaces" => 2,
                "amount" => 1000.00
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

        $response = $this->client->put('api/1.0/Settlement', [
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
}
