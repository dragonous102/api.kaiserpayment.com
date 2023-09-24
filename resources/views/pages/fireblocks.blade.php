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

        <!-- 2nd Box: Un-hide vault accounts -->
        <div class="card mb-3">
          <div class="card-header">Un-hide All Kaiser Vault Accounts</div>
          <div class="card-body">
            <button id="unhideVaultAccounts" class="btn btn-success mb-3" onclick="startUnHideVaultAccounts();">Un-hide All Kaiser Vault Accounts</button>
            <div class="mb-3" id="unHideResult">
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
        url: window.location.origin + '/admin/fireblocks-get-account',
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
        url: window.location.origin + '/admin/fireblocks-get-supported-assets',
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

    let g_hideVaultAccountArray = [];
    let g_totalAccountLength = 0;
    let g_proceedAccountLength = 0;
    function startUnHideVaultAccounts(){
      let html = 'Processing... 0/0 ( 0% ) completed.';
      $('#unHideResult').html(html);

      let dataToSend = {};

      $.ajax({
        url: window.location.origin + '/admin/fireblocks-get-account',
        type: 'POST',
        data: JSON.stringify(dataToSend),
        contentType: 'application/json',
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
          g_hideVaultAccountArray = response.body;
          g_totalAccountLength = g_hideVaultAccountArray.length;
          g_proceedAccountLength = 0;
          unHideVaultAccount();
        },
        error: function(xhr, textStatus) {
          $('#unHideResult').html(xhr.responseJSON.message);
        }
      });
    }

    function unHideVaultAccount(){
      if( g_totalAccountLength === 0 || g_hideVaultAccountArray.length === 0 )
        return;

      let account = g_hideVaultAccountArray.pop();

      if( !account['hiddenOnUI'] ){
        g_proceedAccountLength++;
        let html = 'Processing... ' + (g_proceedAccountLength) + '/' + g_totalAccountLength + ' ( ' + (g_proceedAccountLength*100/g_totalAccountLength).toFixed(1) + '% ) completed.';
        $('#unHideResult').html(html);
        unHideVaultAccount();
        return;
      }

      let dataToSend = {
        id: account['id'],
      };

      $.ajax({
        url: window.location.origin + '/admin/fireblocks-unhide-accounts',
        type: 'POST',
        data: JSON.stringify(dataToSend),
        contentType: 'application/json',
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response1) {
          g_proceedAccountLength++;
          let html = 'Processing... ' + (g_proceedAccountLength) + '/' + g_totalAccountLength + ' ( ' + (g_proceedAccountLength*100/g_totalAccountLength).toFixed(1) + '% ) completed.';
          $('#unHideResult').html(html);
          unHideVaultAccount();
        },
        error: function(xhr, textStatus) {
          g_proceedAccountLength++;
          $('#unHideResult').html(xhr.responseJSON.message);
          unHideVaultAccount();
        }
      });
    }

    function checkAddressStatus(){
      let dataToSend = {
        address: $("#checkDepositAddress").val(),
      };

      $.ajax({
        url: window.location.origin + '/admin/fireblocks-get-account-balance',
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
