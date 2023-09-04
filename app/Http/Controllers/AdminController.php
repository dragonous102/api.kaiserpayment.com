<?php

namespace App\Http\Controllers;

use App\Partner;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminController extends Controller
{
  public function showDashboard(Request $request)
  {
    try {

    }
    catch (\Exception $e) {
      return view('pages.admin', [
        'adminData' => [],
      ]);
    }

    return view('pages.admin', [
      'adminData' => [],
    ]);
  }

  public function addNewPartner(Request $request)
  {
    $code = 200;
    $success = false;
    $timestamp = now()->toIso8601String();
    $body = [];

    try {
      $name = $request->input("name");
      $domain = $request->input("domain");
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
          $message = 'API KEY: '.$this->generateJwtToken($name, $domain, $fee);
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

  public function generateJwtToken($name, $domain, $fee): string
  {
    // Define your secret key (replace with your actual secret key)
    $secretKey = env("JWT_SECRET");

    // Define the token payload (claims)
    $payload = [
      'name' => $name,
      'domain' => $domain,
      'fee' => $fee,
      'exp' => strtotime('+100 years'),
    ];

    // Generate the JWT token
    return JWT::encode($payload, $secretKey, 'HS256');
  }
}
