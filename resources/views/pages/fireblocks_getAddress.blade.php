@extends('layouts.app')

@section('content')
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-6">
        <div class="card mb-3">
          <div class="card-header">Generate New Deposit Address</div>
          <div class="card-body">
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
                        <option value="BNB">BNB</option>
                        <option value="TRX">Tron</option>
                      </select>
                    </div>
                  </div>
                  <div class="form-group" align="right">
                    <button type="button" id="btnGetAddress" onclick="getAddress()" class="btn btn-primary">
                      &nbsp; Get New Deposit Address
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card mb-3">
          <div class="card-header result-header">Result:</div>
          <div class="card-body">
            <div class="row mt-3">
              <div>
                <!-- Form for getting a new deposit address -->
                <form>
                  <div class="form-group row">
                    <label for="order_id" class="col-sm-2 col-form-label">Order Id</label>
                    <div class="col-sm-10">
                      <input type="text" readonly class="form-control" id="order_id" name="orderId" placeholder="Order Id">
                    </div>
                  </div>
                  <div class="form-group row">
                    <label for="result_currency" class="col-sm-2 col-form-label">Currency</label>
                    <div class="col-sm-10">
                      <input type="text" readonly class="form-control" id="result_currency" name="result_currency" placeholder="Currency">
                    </div>
                  </div>
                  <div class="form-group row">
                    <label for="address" class="col-sm-2 col-form-label">Address</label>
                    <div class="col-sm-10">
                      <input type="text" readonly class="form-control" id="address" name="address" placeholder="Address">
                    </div>
                  </div>
                  <div class="form-group row">
                    <label for="status" class="col-sm-2 col-form-label">Status</label>
                    <div class="col-sm-10">
                      <input type="text" readonly class="form-control" id="status" name="status" placeholder="Status">
                    </div>
                  </div>
                  <div class="form-group row">
                    <label for="status" class="col-sm-2 col-form-label">Reason</label>
                    <div class="failed-reason col-sm-10 text-danger mt-2">
                    </div>
                  </div>
                </form>
              </div>
            </div>
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
        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Get New Deposit Address'
      ); // Add the spinner
    }

    // Function to revert the button to the normal state
    function setNormalState() {
      $("#btnGetAddress").removeAttr("disabled"); // Remove the disabled attribute
      $("#btnGetAddress").html("Get New Deposit Address"); // Restore the original text
    }

    function getAddress() {
      setLoadingState();

      let dataToSend = {
        amount: $('#amount').val(),
        productName: $('#productName').val(),
        currency: $('#currency').val(),
      };

      $.ajax({
        url: window.location.origin + '/api/getAddress', // Replace with your API endpoint
        type: 'POST',
        data: JSON.stringify(dataToSend),
        contentType: 'application/json',
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
          'Authorization': 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpZCI6IjEiLCJuYW1lIjoiS0FJU0VSIiwiZG9tYWluIjoiQVBJLktBSVNFUlBBWU1FTlQuQ09NIiwiZmVlIjoiNyIsImV4cCI6NDg0OTU3OTQxNH0.Tqlf_hxqvYu9u-Qw4pUMdHV507CZm48HUnVvfxC8DsQ'
        },
        success: function (response) {
          setNormalState();
          if (response.code === 200) {
            $('.result-header').html('<span class="text-success">Result: '+ response.message +'</span>');
            $('#order_id').removeClass('text-danger').removeClass('text-danger').addClass('text-success').val(response.body.order_id);
            $('#result_currency').removeClass('text-danger').removeClass('text-danger').addClass('text-success').val(response.body.currency);
            $('#address').removeClass('text-danger').removeClass('text-danger').addClass('text-success').val(response.body.address);
            $('#status').removeClass('text-danger').removeClass('text-danger').addClass('text-success').val(response.body.status);
            $('.failed-reason').html('');
          }
          else {
            $('.result-header').html('<span class="text-danger">Result: Failed</span>');
            $('#order_id').removeClass('text-danger').removeClass('text-danger').addClass('text-danger').val(response.body.order_id);
            $('#result_currency').removeClass('text-danger').removeClass('text-danger').addClass('text-danger').val(response.body.currency);
            $('#address').removeClass('text-danger').removeClass('text-danger').addClass('text-danger').val(response.body.address);
            $('#status').removeClass('text-danger').removeClass('text-danger').addClass('text-danger').val(response.body.status);
            $('.failed-reason').html(xhr.responseJSON?.message + ' ' + xhr.responseJSON?.body?.reason);
          }
        },
        error: function (xhr, textStatus) {
          setNormalState();
          $('.result-header').html('<span class="text-danger">Result: Failed</span>');
          $('#order_id').removeClass('text-danger').removeClass('text-danger').addClass('text-danger').val(xhr.responseJSON?.body?.order_id);
          $('#result_currency').removeClass('text-danger').removeClass('text-danger').addClass('text-danger').val(xhr.responseJSON?.body?.currency);
          $('#address').removeClass('text-danger').removeClass('text-danger').addClass('text-danger').val(xhr.responseJSON?.body?.address);
          $('#status').removeClass('text-danger').removeClass('text-danger').addClass('text-danger').val(xhr.responseJSON?.body?.status);
          $('.failed-reason').html(xhr.responseJSON?.message + ' ' + ((xhr?.responseJSON?.body?.reason) ? (xhr?.responseJSON?.body?.reason) : ''));
          console.log(xhr);
        },
      });
    }
  </script>
@endsection
