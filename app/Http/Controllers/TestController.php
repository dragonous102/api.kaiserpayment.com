<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TestController extends Controller
{
  public function home(Request $request): string
  {
    return view('pages.payment');
  }

  public function payment(Request $request): string
  {
    return view('pages.payment');
  }

  public function report(Request $request): string
  {
    return view('pages.report');
  }

  public function confirmation(Request $request): string
  {
    $orderNo = $request->input('orderNo');
    $productDescription = $request->input('productDescription');

    return view('pages.confirmation', [
      'orderNo' => $orderNo,
      'productDescription' => $productDescription,
    ]);
  }

  public function cancellation(Request $request): string
  {
    $orderNo = $request->input('orderNo');
    $productDescription = $request->input('productDescription');

    return view('pages.cancellation', [
      'orderNo' => $orderNo,
      'productDescription' => $productDescription,
    ]);
  }

  public function failed(Request $request): string
  {
    $orderNo = $request->input('orderNo');
    $productDescription = $request->input('productDescription');

    return view('pages.failed', [
      'orderNo' => $orderNo,
      'productDescription' => $productDescription,
    ]);
  }

  public function backend(Request $request): string
  {
    $orderNo = $request->input('orderNo');
    $productDescription = $request->input('productDescription');

    return view('pages.backend', [
      'orderNo' => $orderNo,
      'productDescription' => $productDescription,
    ]);
  }
}
