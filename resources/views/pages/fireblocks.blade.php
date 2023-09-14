@extends('layouts.app')

@section('content')
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-6 mt-3">
        <h5 class="text-secondary mb-3">Fireblocks API Test</h5>
        <!-- 1st Box: Get Account Info -->
        <div class="card mb-3">
          <div class="card-header">Get Account Info</div>
          <div class="card-body">
            <button id="getAccountInfo" class="btn btn-primary" onclick="getAccountInfo();">Get Account Info</button>
            <div id="accountInfo" class="mt-3"></div>
          </div>
        </div>

        <!-- 3rd Box: Supported Assets -->
        <div class="card mb-3">
          <div class="card-header">List of Supported Assets</div>
          <div class="card-body">
            <button id="depositFunds" class="btn btn-success" onclick="getSupportedAssets();">Get Supported Assets</button>
            <div class="mb-3" id="supportedAssets">
            </div>
          </div>
        </div>

        <!-- 4th Box: Get Deposit Status -->
        <div class="card">
          <div class="card-header">Get Deposit Status</div>
          <div class="card-body">
            <div class="mb-3">
              <label for="checkDepositAddress">Deposit Address</label>
              <input type="text" class="form-control" id="checkDepositAddress" name="checkDepositAddress" placeholder="Enter deposited address">
            </div>
            <button id="getDepositStatus" onclick="checkAddressStatus()" class="btn btn-outline-primary">Get Deposit Status</button>
            <div>
              <label class="mt-4">Status:</label>
              <div id="depositStatus" class="mt-3"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    function getAccountInfo(){
      let dataToSend = {};

      $.ajax({
        url: window.location.origin + '/fireblocks-get-account',
        type: 'POST',
        data: JSON.stringify(dataToSend),
        contentType: 'application/json',
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
          var formattedJSON = JSON.stringify(response, null, 2);
          $("#accountInfo").html('<pre>' + formattedJSON + '</pre>');
        },
        error: function(xhr, textStatus) {
          console.log(xhr.responseJSON.message);
        }
      });
    }

    function getSupportedAssets(){
      let dataToSend = {};

      $.ajax({
        url: window.location.origin + '/fireblocks-get-supported-assets',
        type: 'POST',
        data: JSON.stringify(dataToSend),
        contentType: 'application/json',
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
          var formattedJSON = JSON.stringify(response, null, 2);
          $("#supportedAssets").html('<pre>' + formattedJSON + '</pre>');
        },
        error: function(xhr, textStatus) {
          $('#supportedAssets').html(xhr.responseJSON.message);
        }
      });
    }

    function checkAddressStatus(){
      let dataToSend = {
        address: $("#checkDepositAddress").val(),
      };

      $.ajax({
        url: window.location.origin + '/fireblocks-get-account-balance',
        type: 'POST',
        data: JSON.stringify(dataToSend),
        contentType: 'application/json',
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
          var formattedJSON = JSON.stringify(response, null, 2);
          $("#depositStatus").html('<pre>' + formattedJSON + '</pre>');
        },
        error: function(xhr, textStatus) {
          $('#depositStatus').html(xhr.responseJSON.message);
        }
      });
    }
  </script>
@endsection
