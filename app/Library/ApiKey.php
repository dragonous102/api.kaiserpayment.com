<?php
namespace App\Library;
use App\Partner;
use Exception;
use Firebase\JWT\JWT;

class ApiKey
{
  public static function generateJwtToken($id, $name, $domain, $fee): string
  {
    // Define your secret key (replace with your actual secret key)
    $secretKey = config('api_keys.JWT_SECRET');

    // Define the token payload (claims)
    $payload = [
      'id'=>$id,
      'name' => $name,
      'domain' => $domain,
      'fee' => $fee,
      'exp' => strtotime('+100 years'),
    ];

    // Generate the JWT token
    return JWT::encode($payload, $secretKey, 'HS256');
  }

  public static function parseJwtToken($jwt): array
  {
    $result = [];
    try {

      $decoded = JWT::decode($jwt, config('api_keys.JWT_SECRET'), ['HS256']);
      $result['id'] = $decoded->id;
      $result['name'] = $decoded->name;
      $result['domain'] = $decoded->domain;
      /*$result['crypto_fee'] = $decoded->crypto_fee;*/
      $result['fee'] = $decoded->fee;
    } catch (Exception $e) {
      $result['error'] = $e->getMessage();
    }
    return $result;
  }

  public static function getApiKeyFromDomain($domain){
    //echo '<br>'.$domain.'<br>';
    $apiKeys = config('api_keys');

    foreach ($apiKeys as $key => $value) {
      if( $domain == $key )
        return $value;
    }
    return null;
  }

  public static function isValidApiKey(?Partner $dbPartner, $apiKeyPartner): string
  {
    if ($apiKeyPartner == null)
      return 'UN_REGISTERED_API_KEY';

    if ($dbPartner === null)
      return 'UN_REGISTERED_API_KEY';
    elseif ($apiKeyPartner['id'] != $dbPartner->id ||
      $apiKeyPartner['name'] != $dbPartner->name ||
      /*$apiKeyPartner['crypto_fee'] != $dbPartner->crypto_fee ||*/
      $apiKeyPartner['domain'] != $dbPartner->domain ||
      $apiKeyPartner['fee'] != $dbPartner->fee)
      return 'NOT_MATCHED_API_KEY';
    else
      return 'VALID_API_KEY';
  }
}
