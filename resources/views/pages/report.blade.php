@extends('layouts.app')

@section('content')
  <div class="container">
    <h5 class="text-secondary mb-3">https://api.kaiserpayment.com/api/getReport</h5>
    <div class="row">
      <div class="col-md-12">
        <div class="error-box card border-danger mb-3" style="display: none">
          <div class="card-header bg-danger text-white">Report error</div>
          <div class="card-body text-danger">
          </div>
        </div>
        <div class="card">
          <div class="card-header">Report</div>
          <div class="card-body">
            <form class="mb-3">
              <div class="row">
                <div class="col-md-9">
                  <div class="row">
                    <div class="col-md-2">
                      <label for="name">Name:</label>
                      <input type="text" class="form-control" id="name" name="name" placeholder="partner name">
                    </div>
                    <div class="col-md-2">
                      <label for="orderNo">Order Number:</label>
                      <input type="text" class="form-control" id="orderNo" name="orderNo" placeholder="order number">
                    </div>
                    <div class="col-md-2">
                      <label for="fromDate">From Date:</label>
                      <input type="text" class="form-control" id="fromDate" name="fromDate" placeholder="YYYY-MM-DD">
                    </div>
                    <div class="col-md-2">
                      <label for="toDate">To Date:</label>
                      <input type="text" class="form-control" id="toDate" name="toDate" placeholder="YYYY-MM-DD">
                    </div>
                    <div class="col-md-2">
                      <label for="email">Email Address:</label>
                      <input type="email" class="form-control" id="email" name="email" placeholder="email address">
                    </div>
                    <div class="col-md-2">
                      <label for="status">Status:</label>
                      <select class="form-control" id="status" name="status">
                        <option value>All Status</option>
                        <option value="PCPS">Pre-stage</option>
                        <option value="I">Initial</option>
                        <option value="A">Approved</option>
                        <option value="V">Voided</option>
                        <option value="S">Settled</option>
                        <option value="R">Refund</option>
                        <option value="P">Pending Payment</option>
                        <option value="F">Rejected</option>
                        <option value="E">Expired</option>
                        <option value="C">Cancelled</option>
                      </select>
                    </div>
                  </div>
                </div>
                <div class="col-md-3">
                  <button type="button" class="get-report btn btn-primary mt-3"><i class="fas fa-search"></i> Search</button>
                  <button type="button" class="get-report btn btn-success mt-3"><i class="fas fa-download"></i> Download xlsx</button>
                </div>
              </div>
            </form>
            <hr>
            <table class="table">
              <thead>
              <tr>
                <th>No</th>
                <th>Order No</th>
                <th>Name</th>
                <th>Email Address</th>
                <th>Date</th>
                <th>Amount</th>
                <th>Fee</th>
                <th>Product Name</th>
                <th>Card Holder Name</th>
                <th>Status</th>
                <th>Domain</th>
              </tr>
              </thead>
              <tbody class="my-tbody">
              </tbody>
            </table>
          </div>
        </div>
        <div class="col-md-12 mt-3">
          <div class="card">
            <div class="card-header">API Response</div>
            <div class="card-body api-response"></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="{{ asset('js/http_code.jquery.com_jquery-3.6.0.js', config('env') == 'local') }}"></script>

  <script>
    function convertDateString( dateString ){

      const date = new Date(dateString);
      const year = date.getUTCFullYear() % 100; // Get last 2 digits of the year
      const month = date.getUTCMonth() + 1; // Month is zero-based, so add 1
      const day = date.getUTCDate();
      const hours = date.getUTCHours();
      const minutes = date.getUTCMinutes();
      const seconds = date.getUTCSeconds();

      return `${year}/${month}/${day} ${hours}:${minutes}:${seconds}`;
    }

    $(document).ready(function(){
      $('.get-report').click(function (){
        let dataToSend = {
          orderNo: $('#orderNo').val(),
          fromDate: $('#fromDate').val(),
          toDate: $('#toDate').val(),
          email: $('#email').val(),
          name: $('#name').val(),
          status: $('#status').val(),
        };

        $.ajax({
          url: window.location.origin + '/api/getReport',
          type: 'POST',
          data: JSON.stringify(dataToSend),
          headers: {
            'Authorization': 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpZCI6IjEiLCJuYW1lIjoiS0FJU0VSIiwiZG9tYWluIjoiQVBJLktBSVNFUlBBWU1FTlQuQ09NIiwiZmVlIjoiNyIsImV4cCI6NDg0OTU3OTQxNH0.Tqlf_hxqvYu9u-Qw4pUMdHV507CZm48HUnVvfxC8DsQ'
          },
          contentType: 'application/json',
          success: function(response) {
            var formattedJSON = JSON.stringify(response, null, 2);
            $(".api-response").html('<pre>' + formattedJSON + '</pre>');

            if( response['code'] !== 200 ){
              $('.error-box').show();
              $('.error-box .card-header').html(response['message']);
              $('.error-box .card-body').html(response['body']);
              $(".api-response").removeClass('text-danger').removeClass('text-success').addClass('text-danger');
            }
            else{
              $('.error-box').hide();
              $(".api-response").removeClass('text-danger').removeClass('text-success').addClass('text-success');

              var html = '';
              var data = response['body'];
              for( var i = 0; i < data.length; i++){
                html += '<tr>\
              <td>'+ (i+1) +'</td>\
              <td>'+ (data[i].orderNo == null ? '' : data[i].orderNo) +'</td>\
              <td>'+ (data[i].name == null ? '' : data[i].name) +'</td>\
              <td>'+ (data[i].email_address == null ? '' : data[i].email_address) +'</td>\
              <td>'+ (data[i].created_at == null ? '' : convertDateString(data[i].created_at)) +'</td>\
              <td>'+ (data[i].amount == null ? '' : data[i].amount) +'</td>\
              <td>'+ ((data[i].fee == null || data[i].fee === 0) ? '' : data[i].fee) +'</td>\
              <td>'+ ((data[i].product_name == null || data[i].product_name === 0) ? '' : data[i].product_name) +'</td>\
              <td>'+ ((data[i].card_holder_name == null || data[i].card_holder_name === 0) ? '' : data[i].card_holder_name) +'</td>\
              <td>'+ (data[i].status == null ? '' : data[i].status) +'</td>\
              <td>'+ (data[i].domain == null ? '' : data[i].domain) +'</td>\
              </tr>';
              }
              $('.my-tbody').html(html);
            }
          },
          error: function(xhr, textStatus, errorThrown) {
            // Handle errors here
            var formattedJSON = JSON.stringify(xhr.responseJSON, null, 2);
            $(".api-response").html('<pre>' + formattedJSON + '</pre>');
            $(".api-response").removeClass('text-danger').removeClass('text-success').addClass('text-danger');

            $('.error-box').show();
            $('.error-box .card-header').html(xhr.responseJSON.message);
            $('.error-box .card-body').html(xhr.responseJSON.body);
          }
        });
      })
    });
  </script>
@endsection
