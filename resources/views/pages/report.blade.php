@extends('layouts.app')

@section('content')
  <div class="container">
    <div class="row">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header">Report</div>
          <div class="card-body">
            <form class="mb-3">
              <div class="row">
                <div class="col-md-11">
                  <div class="row">
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
                      <label for="name">Name:</label>
                      <input type="text" class="form-control" id="name" name="name" placeholder="card holder name">
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
                <div class="col-md-1">
                  <button type="button" class="get-report btn btn-primary mt-3">Search</button>
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
                <th>Status</th>
                <th>Agent</th>
              </tr>
              </thead>
              <tbody class="my-tbody">
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  <script>
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
          contentType: 'application/json',
          success: function(response) {
            var html = '';
            var data = response['body'];
            for( var i = 0; i < data.length; i++){
              html += '<tr>\
              <td>'+ (i+1) +'</td>\
              <td>'+ (data[i].orderNo == null ? '' : data[i].orderNo) +'</td>\
              <td>'+ (data[i].name == null ? '' : data[i].name) +'</td>\
              <td>'+ (data[i].email == null ? '' : data[i].email) +'</td>\
              <td>'+ (data[i].date == null ? '' : data[i].date) +'</td>\
              <td>'+ (data[i].amount == null ? '' : data[i].amount) +'</td>\
              <td>'+ ((data[i].fee == null || data[i].fee === 0) ? '' : data[i].fee) +'</td>\
              <td>'+ (data[i].status == null ? '' : data[i].status) +'</td>\
              <td>'+ (data[i].partner == null ? '' : data[i].partner) +'</td>\
              </tr>';
            }
            $('.my-tbody').html(html);
          },
          error: function(xhr, textStatus, errorThrown) {
            // Handle errors here
            console.log('Error:', errorThrown);
          }
        });
      })
    });
  </script>
@endsection
