<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
  public function showLoginPage(Request $request){
    return view('pages.admin_login');
  }

  public function login(Request $request)
  {
    if( $request->input('name') != 'minamide@optlynx.com' ){
      return redirect()->route('login');
    }

    $credentials = $request->only('name', 'password');

    if (Auth::attempt($credentials)) {
      return redirect()->route('page.home');
    }
    else{
      return redirect()->route('login');
    }
  }

  public function showResetPasswordPage(Request $request){
    return view('pages.reset-password');
  }

  public function logout(Request $request){
    Auth::logout(); // Logs the user out

    $request->session()->invalidate(); // Invalidates the user's session
    $request->session()->regenerateToken(); // Regenerates a new CSRF token

    return redirect('/');
  }

  public function resetPassword(Request $request): JsonResponse
  {

    // Validate the request data
    $request->validate([
      'old_password' => 'required',
      'new_password' => 'required|min:6|confirmed',
    ]);

    // Get the authenticated user
    $user = Auth::user();

    // Check if the old password matches the user's current password
    if (!Hash::check($request->input('old_password'), $user->password)) {
      return response()->json(['message' => 'Old password is incorrect.'], 400);
    }

    // Update the user's password with the new one
    $user->password = Hash::make($request->input('new_password'));
    $user->save();

    return response()->json(['message' => 'Password reset successfully.'], 200);
  }
}
