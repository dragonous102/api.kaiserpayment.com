@extends('layouts.app')

@section('content')
  <div class="row justify-content-center">
    <div class="col-md-6">
      <h2 class="text-info text-center mb-3">Payment Backend</h2>
      <div class="card">
        <div class="card-header">Payment Information</div>
        <div class="card-body">
          <p><strong>Order No:</strong> {{ $orderNo }}</p>
          <p><strong>Product Description:</strong> {{ $productDescription }}</p>
        </div>
      </div>
    </div>
  </div>
@endsection
