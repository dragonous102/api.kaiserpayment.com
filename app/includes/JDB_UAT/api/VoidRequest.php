<?php

namespace App\includes\JDB_UAT\api;

use App\includes\JDB_UAT\ActionRequest;
use App\includes\JDB_UAT\SecurityData;
use Carbon\Carbon;
use Exception;


class VoidRequest extends ActionRequest
{
    public function Execute(): string
    {
        $officeId = "DEMOOFFICE";
        $orderNo = "1643362945102"; //OrderNo can be Refund/Void one time only
        $productDescription = "Sample request for 1643362945102";

        $request = [
            "officeId" => $officeId,
            "orderNo" => $orderNo,
            "productDescription" => $productDescription,
            "issuerApprovalCode" => "140331", // approvalCode of order place (Payment api) response
            "actionBy" => "System",
            "voidAmount" => [
                "amountText" => "000000100000",
                "currencyCode" => "THB",
                "decimalPlaces" => 2,
                "amount" => 1000.00
            ],
        ];

        $stringRequest = json_encode($request);

        $response = $this->client->post('api/1.0/Void', [
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
        $orderNo = "1643362945102"; //OrderNo can be Refund/Void one time only
        $productDescription = "Sample request for 1643362945102";

        $request = [
            "officeId" => $officeId,
            "orderNo" => $orderNo,
            "productDescription" => $productDescription,
            "issuerApprovalCode" => "140331", // approvalCode of order place (Payment api) response
            "actionBy" => "System",
            "voidAmount" => [
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

        $response = $this->client->post('api/1.0/Void', [
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
