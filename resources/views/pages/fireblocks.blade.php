@extends('layouts.app')

@section('content')
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-12">
        <!-- 1st Box: Get Account Info -->
        <div class="card mb-3">
          <div class="card-header">Get Account Info</div>
          <div class="card-body">
            <button id="getAccountInfo" class="btn btn-primary" onclick="getAccountInfo();">Get Account Info</button>
            <div id="accountInfo" class="mt-3"></div>
          </div>
        </div>

        <!-- 2nd Box: Get New Deposit Address -->
        <div class="card mb-3">
          <div class="card-header">Get New Deposit Address</div>
          <div class="card-body">
            <div class="row mt-3">
              <div class="col-md-4">
                <button id="getNewBtcDepositAddress" onclick="getNewBtcDepositAddress()" class="btn btn-primary">Get New BTC Deposit Address</button>
              </div>
              <div id="newBtcDepositAddress" class="col-md-8"></div>
            </div>
            <div class="row mt-3">
              <div class="col-md-4">
                <button id="getNewUsdtDepositAddress" onclick="getNewUsdtDepositAddress()" class="btn btn-primary">Get New USDT Deposit Address</button>
              </div>
              <div id="newUsdtDepositAddress" class="col-md-8"></div>
            </div>
            <div class="row mt-3">
              <div class="col-md-4">
                <button id="getNewEthDepositAddress" onclick="getNewEthDepositAddress()" class="btn btn-primary">Get New ETH Deposit Address</button>
              </div>
              <div id="newEthDepositAddress" class="col-md-8"></div>
            </div>
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
          <div class="card-body row">
            <div class="mb-3 col-md-6">
              <label for="checkAccountName">Account Name</label>
              <input type="text" class="form-control" id="checkAccountName" name="checkAccountName" placeholder="Enter deposited account name">
            </div>
            <div class="mb-3 col-md-6">
              <label for="checkDepositAddress">Deposited Address</label>
              <input type="text" class="form-control" id="checkDepositAddress" name="checkDepositAddress" placeholder="Enter deposited address">
            </div>
            <button id="getDepositStatus" onclick="checkAddressStatus()" class="offset-md-8 col-md-3 btn btn-outline-primary">Get Deposit Status</button>
            <div class="row">
              <label class="col-md-1">Status:</label>
              <div id="depositStatus" class="mt-3 col-md-10"></div>
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

    function getNewBtcDepositAddress(){
      let dataToSend = {
        currency: "BTC",
      };

      $.ajax({
        url: window.location.origin + '/fireblocks-get-new-btc-deposit-address',
        type: 'POST',
        data: JSON.stringify(dataToSend),
        contentType: 'application/json',
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
          if( response.code === 200){
            $('#newBtcDepositAddress').html('accountName: ' + response.body[0].accountName + '<br>address: ' + response.body[0].address + '<br>legacyAddress: ' + response.body[0].legacyAddress);
          }
          else{
            $('#newBtcDepositAddress').html(response.message);
          }
        },
        error: function(xhr, textStatus) {
          $('#newBtcDepositAddress').html(xhr.responseJSON.message);
        }
      });
    }

    function getNewUsdtDepositAddress(){
      let dataToSend = {
        currency: "USDT",
      };

      $.ajax({
        url: window.location.origin + '/fireblocks-get-new-btc-deposit-address',
        type: 'POST',
        data: JSON.stringify(dataToSend),
        contentType: 'application/json',
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
          if( response.code === 200){
            $('#newUsdtDepositAddress').html('accountName: ' + response.body[0].accountName + '<br>address: ' + response.body[0].address + '<br>legacyAddress: ' + response.body[0].legacyAddress);
          }
          else{
            $('#newUsdtDepositAddress').html(response.message);
          }
        },
        error: function(xhr, textStatus) {
          $('#newUsdtDepositAddress').html(xhr.responseJSON.message);
        }
      });
    }

    function getNewEthDepositAddress(){
      let dataToSend = {
        currency: "ETH",
      };

      $.ajax({
        url: window.location.origin + '/fireblocks-get-new-btc-deposit-address',
        type: 'POST',
        data: JSON.stringify(dataToSend),
        contentType: 'application/json',
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
          if( response.code === 200){
            $('#newEthDepositAddress').html('accountName: ' + response.body[0].accountName + '<br>address: ' + response.body[0].address + '<br>legacyAddress: ' + response.body[0].legacyAddress);
          }
          else{
            $('#newEthDepositAddress').html(response.message);
          }
        },
        error: function(xhr, textStatus) {
          $('#newEthDepositAddress').html(xhr.responseJSON.message);
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
        accountName: $("#checkAccountName").val(),
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
