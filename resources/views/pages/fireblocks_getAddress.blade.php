@extends('layouts.app')

@section('content')
  <div class="container">
    <h5 class="text-secondary mb-3">https://api.kaiserpayment.com/api/getCryptoPaymentAddress</h5>
    <div class="row justify-content-center">
      <div class="col-md-6">
        <div class="card mb-3">
          <div class="card-header">Parameters of Get Crypto Payment Address API</div>
          <div class="card-body" style="height: 320px;">
            <div class="row mt-3">
              <div>
                <!-- Form for getting a new deposit address -->
                <form id="depositAddressForm">
                  <div class="form-group row">
                    <label for="amount" class="col-sm-3 col-form-label">Amount</label>
                    <div class="col-sm-9">
                      <input type="number" class="form-control" id="amount" name="amount" placeholder="Enter the amount">
                    </div>
                  </div>
                  <div class="form-group row">
                    <label for="productName" class="col-sm-3 col-form-label">Product Name</label>
                    <div class="col-sm-9">
                      <input type="text" class="form-control" id="productName" name="productName" placeholder="Enter the product name">
                    </div>
                  </div>
                  <div class="form-group row">
                    <label for="currency" class="col-sm-3 col-form-label">Currency</label>
                    <div class="col-sm-9">
                      <select class="form-control" id="currency" name="currency">
                        <option value>Select Currency</option>
                        <option value="BTC">BTC</option>
                        <option value="ETH">ETH</option>
                        <option value="USDT_ERC20">Tether USD (Ethereum)</option>
                        <option value="TRX_USDT_S2UZ">USD Tether (Tron)</option>
                        <option value="USDT_BSC">Binance-Peg Tether (BSC)</option>
                        <option value="USDT_POLYGON">(PoS) Tether USD (Polygon)</option>
                        <option value="BUSD_BSC">Binance-Peg BUSD (BSC)</option>
                        <option value="USDC">USD Coin</option>
                        <option value="USDC_POLYGON">USDC (Polygon)</option>
                        <option value="BNB_BSC">BNB Smart Chain</option>
                        <option value="BNB_TEST">BNB_TEST</option>
                        <option value="TRX">Tron</option>
                      </select>
                    </div>
                  </div>
                  <div class="form-group row">
                    <label for="email" class="col-sm-3 col-form-label">Email</label>
                    <div class="col-sm-9">
                      <input type="text" class="form-control" id="email" name="email" placeholder="Enter the Email Address">
                    </div>
                  </div>
                  <div class="form-group row">
                    <label for="name" class="col-sm-3 col-form-label">Name</label>
                    <div class="col-sm-9">
                      <input type="text" class="form-control" id="name" name="name" placeholder="Enter your Name">
                    </div>
                  </div>
                </form>
                <div class="mt-5" align="right">
                  <button type="button" id="btnGetAddress" onclick="getAddress()" class="btn btn-primary">
                    &nbsp; Call API
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card">
          <div class="card-header">API Response</div>
          <div class="card-body api-response" style="height: 320px;">
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- jQuery -->
  {{--<script src="{{ asset('js/http_code.jquery.com_jquery-3.6.0.js', config('env') == 'local') }}"></script>--}}
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script>
    // Function to set the button to the loading state
    function setLoadingState() {
      $("#btnGetAddress").attr("disabled", true); // Disable the button
      $("#btnGetAddress").html(
        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Call API'
      ); // Add the spinner
    }

    // Function to revert the button to the normal state
    function setNormalState() {
      $("#btnGetAddress").removeAttr("disabled"); // Remove the disabled attribute
      $("#btnGetAddress").html("Call API"); // Restore the original text
    }

    function getAddress() {
      setLoadingState();

      let dataToSend = {
        amount: $('#amount').val(),
        productName: $('#productName').val(),
        currency: $('#currency').val(),
        email: $('#email').val(),
        name: $('#name').val(),
      };

      $.ajax({
        url: window.location.origin + '/api/getCryptoPaymentAddress', // Replace with your API endpoint
        type: 'POST',
        data: JSON.stringify(dataToSend),
        contentType: 'application/json',
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
          'Authorization': 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpZCI6IjEiLCJuYW1lIjoiS0FJU0VSIiwiZG9tYWluIjoiQVBJLktBSVNFUlBBWU1FTlQuQ09NIiwiZmVlIjoiOSIsImV4cCI6NDg1MzM5NTc1Nn0.hQUB1rNcP6uIFyo8NxokcxkDcHYnMbQRGaLwB3P-Wp0'
        },
        success: function (response) {
          setNormalState();
          var formattedJSON = JSON.stringify(response, null, 2);
          $(".api-response").html('<pre>' + formattedJSON + '</pre>');

          if (response.code === 200) {
            $(".api-response").removeClass('text-danger').removeClass('text-success').addClass('text-success');
          }
          else {
            $(".api-response").removeClass('text-danger').removeClass('text-success').addClass('text-danger');
          }
        },
        error: function (xhr, textStatus) {
          setNormalState();
          var formattedJSON = JSON.stringify(xhr.responseJSON, null, 2);
          $(".api-response").html('<pre>' + formattedJSON + '</pre>');
          $(".api-response").removeClass('text-danger').removeClass('text-success').addClass('text-danger');
        },
      });
    }
  </script>
@endsection
