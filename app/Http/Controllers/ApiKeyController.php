<?php

namespace App\Http\Controllers;

use App\Partner;
use Illuminate\Http\Request;
use App\Library\ApiKey;
use Illuminate\Support\Facades\Artisan;

class ApiKeyController extends Controller
{
  public function showDashboard(Request $request)
  {
    try {
      $partners = Partner::orderBy('created_at', 'desc')->get();

      foreach ($partners as $partner) {
        $api_key = ApiKey::getApiKeyFromDomain($partner->domain);

        if ($api_key === null || strlen(trim($api_key)) == 0) {
          $partner->api_key = 'MISSING';
        }
        else {
          $savedData = ApiKey::parseJwtToken($api_key);
          if( isset($savedData['error'])) {
            $partner->api_key = 'INVALID';
            continue;
          }

          if (
            $savedData['id'] == $partner->id &&
            $savedData['name'] == $partner->name &&
            $savedData['service_type'] == $partner->service_type &&
            $savedData['domain'] == $partner->domain &&
            $savedData['fee'] == $partner->fee) {
            $partner->api_key = $api_key;
          } else {
            $partner->api_key = 'INVALID';
          }
        }
      }
    } catch (\Exception $e) {
      $partners = [];
    }

    return view('pages.apikey', compact('partners'));
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
      $crypto_fee = $request->input("crypto_fee");
      $status = $request->input("status");
      $service_type = $request->input("service_type");

      // Check if a partner with the same name or domain already exists
      $existingPartner = Partner::where('name', $name)->orWhere('domain', $domain)->first();

      if ($existingPartner) {
        $code = 400;
        $message = "A partner with the same name or domain already exists.";
      } elseif (empty($name) || empty($domain) || empty($fee) || empty($crypto_fee) || !is_numeric($fee) || $fee < 0 || empty($status) || empty($service_type)) {
        $code = 400;
        $message = "Invalid input data. Please provide valid values for all fields.";
      } else {
        // Validation passed, create a new partner record
        $newPartner = Partner::create([
          'name' => $name,
          'domain' => $domain,
          'fee' => $fee,
          'crypto_fee' => $crypto_fee,
          'status' => $status,
          'service_type' => $service_type
        ]);

        if ($newPartner) {
          $message = 'API KEY: '.ApiKey::generateJwtToken($newPartner->id, $name, $domain, $fee, $service_type);
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
        $existingPartner->api_key = ApiKey::getApiKeyFromDomain($existingPartner->domain);
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
      $crypto_fee = $request->input("crypto_fee");
      $status = $request->input("status");
      $service_type = $request->input("service_type");
      $existingPartner = Partner::where('id', $id)->first();

      if (empty($name) || empty($domain) || empty($fee) || empty($crypto_fee) || !is_numeric($fee) || $fee < 0 || !is_numeric($status) || empty($service_type)) {
        $code = 400;
        $message = "Invalid input data. Please provide valid values for all fields.";
      } else if ($existingPartner == null){
        $code = 400;
        $message = "The is no partner in database.";
      }
      else {
        if( $existingPartner->name == $name &&
          $existingPartner->domain == $domain &&
          $existingPartner->fee == $fee &&
          $existingPartner->id == $id &&
          $existingPartner->service_type == $service_type){
          if($existingPartner->status != $status || $existingPartner->crypto_fee != $crypto_fee ){
            $existingPartner->status = $status;
            $existingPartner->crypto_fee = $crypto_fee;
            $existingPartner->save();
          }
          $existingPartner->api_key = ApiKey::getApiKeyFromDomain($domain);
          $success = true;
          $message = "Partner information updated successfully. But because you did not change API key information, will not generate new API key.";
          $body = $existingPartner;
        }
        else {
          $existingPartner->name = $name;
          $existingPartner->domain = $domain;
          $existingPartner->fee = $fee;
          $existingPartner->crypto_fee = $crypto_fee;
          $existingPartner->status = $status;
          $existingPartner->service_type = $service_type;

          $existingPartner->save();
          $existingPartner->api_key = ApiKey::generateJwtToken($id, $name, $domain, $fee, $service_type);

          $success = true;
          $message = "Partner information updated successfully. Please remember to manually update the API_KEY in the environment file to ensure proper functionality.";
          $body = $existingPartner;
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
        $partner->delete();
        $message = "A partner was deleted successfully.";
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

    try {
      $id = $request->input("id");
      $dbPartner = Partner::find($id);
      if ($dbPartner) {
        $api_key = ApiKey::getApiKeyFromDomain($dbPartner->domain);
        if ($api_key === null || strlen(trim($api_key)) == 0) {
          $message = "Any API KEY was not set in env file and config file for this partner or did not applied to the Kaiser server.";
          $body = ['msg'=>"NO_API_KEY", 'api_key'=>''];
        }
        else {
          $apiKeyPartner = ApiKey::parseJwtToken($api_key);
          if ($apiKeyPartner['id'] != $dbPartner->id ||
            $apiKeyPartner['name'] != $dbPartner->name ||
            $apiKeyPartner['domain'] != $dbPartner->domain ||
            $apiKeyPartner['fee'] != $dbPartner->fee ||
            $apiKeyPartner['service_type'] != $dbPartner->service_type) {
            $message = "<p class='text-danger'> The information in the API KEY does not match the saved partner's information.</p><br>API KEY: <br>";
            $body = ['msg'=>"INVALID_API_KEY", 'api_key'=>$api_key];
          }
          else {
            $success = true;
            $message = "<p class='text-success'> API KEY: </p>";
            $body = ['msg'=>"VALID_API_KEY", 'api_key'=>$api_key];
          }
        }
      }
      else {
        $code = 400;
        $message = "<p class='text-danger'>Invalid partner.</p>";
        $body = ['msg'=>"NO_API_KEY", 'api_key'=>''];
      }
    }
    catch (\Exception $e) {
      $code = 500;
      $message = "<p class='text-danger'>".$e->getMessage()."</p>";
      $body = ['msg'=>"NO_API_KEY", 'api_key'=>''];
    }

    return response()->json([
      'code' => $code,
      'success' => $success,
      'message' => $message,
      'timestamp' => $timestamp,
      'body' => $body,
    ])->setStatusCode($code);
  }

  public function applyApiKey(Request $request)
  {
    $code = 200;
    $timestamp = now()->toIso8601String();
    $body = '';

    try {
      $success = true;
      Artisan::call('config:clear');

      $output1 = Artisan::output();
      $message = "The API KEY was applied to the Kaiser server successfully. Check the API KEY working status. ".$output1;
    } catch (\Exception $e) {
      $code = 500;
      $success = false;
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
}
