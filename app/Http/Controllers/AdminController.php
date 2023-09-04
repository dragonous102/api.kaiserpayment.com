<?php

namespace App\Http\Controllers;

use App\Partner;
use Exception;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;

class AdminController extends Controller
{
  public function showDashboard(Request $request)
  {
    try {
      $partners = Partner::orderBy('updated_at', 'desc')->get();

      foreach ($partners as $partner) {
        $api_key = $this->getApiKeyFromDomain($partner->domain);

        if ($api_key === null || strlen(trim($api_key)) == 0) {
          $partner->api_key = 'MISSING';
        } else {
          //echo $api_key;
          $savedData = $this->parseJwtToken($api_key);
          if( isset($savedData['error'])) {
            $partner->api_key = 'INVALID';
            continue;
          }
          //echo json_encode($savedData);
          if (
            $savedData['name'] == $partner->name &&
            $savedData['domain'] == $partner->domain &&
            $savedData['fee'] == $partner->fee
          ) {
            $partner->api_key = $api_key;
          } else {
            $partner->api_key = 'INVALID';
          }
        }
      }
    } catch (\Exception $e) {
      $partners = [];
    }

    return view('pages.admin', compact('partners'));
  }

  public function addNewPartner(Request $request)
  {
    $code = 200;
    $success = false;
    $timestamp = now()->toIso8601String();
    $body = [];

    try {
      $name = strtoupper($request->input("name"));
      $domain = strtoupper($request->input("domain"));
      $fee = $request->input("fee");
      $status = $request->input("status");

      // Check if a partner with the same name or domain already exists
      $existingPartner = Partner::where('name', $name)->orWhere('domain', $domain)->first();

      if ($existingPartner) {
        $code = 400;
        $message = "A partner with the same name or domain already exists.";
      } elseif (empty($name) || empty($domain) || empty($fee) || !is_numeric($fee) || empty($status)) {
        $code = 400;
        $message = "Invalid input data. Please provide valid values for all fields.";
      } else {
        // Validation passed, create a new partner record
        $newPartner = Partner::create([
          'name' => $name,
          'domain' => $domain,
          'fee' => $fee,
          'status' => $status,
        ]);

        if ($newPartner) {
          $message = 'API KEY: '.$this->generateJwtToken($newPartner->id, $name, $domain, $fee);
          $success = true;
          $body = $newPartner;
        } else {
          $code = 500;
          $message = "Failed to add new partner.";
        }
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

  public function getPartner(Request $request)
  {
    $code = 200;
    $success = false;
    $timestamp = now()->toIso8601String();
    $body = [];

    try {
      $id = $request->input("id");
      $existingPartner = Partner::where('id', $id)->first();

      if ($existingPartner == null) {
        $code = 400;
        $message = "The is no partner in database.";
      }
      else {
        $message = "partner";
        $existingPartner->api_key = $this->getApiKeyFromDomain($existingPartner->domain);
        $success = true;
        $body = $existingPartner;
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

  public function updatePartner(Request $request)
  {
    $code = 200;
    $success = false;
    $timestamp = now()->toIso8601String();
    $body = [];

    try {
      $id = $request->input("id");
      $name = strtoupper($request->input("name"));
      $domain = strtoupper($request->input("domain"));
      $fee = $request->input("fee");
      $status = $request->input("status");
      $existingPartner = Partner::where('id', $id)->first();

      if (empty($name) || empty($domain) || empty($fee) || !is_numeric($fee) || !is_numeric($status)) {
        $code = 400;
        $message = "Invalid input data. Please provide valid values for all fields.";
      } else if ($existingPartner == null){
        $code = 400;
        $message = "The is no partner in database.";
      }
      else {
        $existingPartner->name = $name;
        $existingPartner->domain = $domain;
        $existingPartner->fee = $fee;
        $existingPartner->status = $status;

        $existingPartner->save();
        $existingPartner->api_key = $this->generateJwtToken($id, $name, $domain, $fee);

        $success = true;
        $message = "Partner information updated successfully. Please remember to manually update the API_KEY in the environment file to ensure proper functionality.";
        $body = $existingPartner;
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

  public function deletePartner(Request $request)
  {
    $code = 200;
    $success = false;
    $timestamp = now()->toIso8601String();
    $body = [];

    try {
      $id = $request->input("id");
      $partner = Partner::find($id);
      if ($partner) {
        $success = true;
        $partner->forceDelete();
        $message = "A partner deleted successfully.";
      } else {
        $code = 400;
        $message = "Failed to delete partner.";
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

  public function getApiKey(Request $request)
  {
    $code = 200;
    $success = false;
    $timestamp = now()->toIso8601String();
    $body = '';

    try {
      $id = $request->input("id");
      $partner = Partner::find($id);
      if ($partner) {
        $success = true;
        $api_key = $this->getApiKeyFromDomain($partner->domain);

        if ($api_key === null || strlen(trim($api_key)) == 0) {
          $body = "API KEY was not set in env file and config file.";
        } else {
          $savedData = $this->parseJwtToken($api_key);
          if (
            $savedData['name'] == $partner->name &&
            $savedData['domain'] == $partner->domain &&
            $savedData['fee'] == $partner->fee
          ) {
            $body = "<p class='text-success'>".$api_key."</p>";
          } else {
            $body = "<p class='text-danger'> The information in the API KEY does not match the saved partner's information.</p> <br>API KEY: <br><p class='text-danger'>".$api_key.'</p>';
          }
        }
        $message = "API KEY";
      } else {
        $code = 400;
        $message = "Failed to get API KEY.";
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

  public function generateJwtToken($id, $name, $domain, $fee): string
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

  public function parseJwtToken($jwt): array
  {
    $result = [];
    try {

      $decoded = JWT::decode($jwt, config('api_keys.JWT_SECRET'), ['HS256']);
      $result['id'] = $decoded->id;
      $result['name'] = $decoded->name;
      $result['domain'] = $decoded->domain;
      $result['fee'] = $decoded->fee;
    } catch (Exception $e) {
      $result['error'] = $e->getMessage();
    }
    return $result;
  }

  public function getApiKeyFromDomain($domain){
    $apiKeys = config('api_keys');

    foreach ($apiKeys as $key => $value) {
      if( $domain == $key )
        return $value;
    }
    return null;
  }
}
