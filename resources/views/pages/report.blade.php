@extends('layouts.app')

@section('content')
  <style>
    .dataTables_paginate{
      margin-top: 20px !important;
    }
  </style>
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
          <div class="card-header">Search Parameters of Get JDB Payment Report API</div>
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
                  <button type="button" class="get-report btn btn-primary mt-3"><i class="fas fa-search"></i> API Call</button>
                  <button type="button" class="get-report btn btn-success mt-3"><i class="fas fa-download"></i> Download xlsx</button>
                </div>
              </div>
            </form>
            <hr>
            <table class="table" id="reportTable">
              <thead>
              <tr>
                <th>No</th>
                <th>Order No</th>
                <th>Name</th>
                <th style="width: 200px;">Email Address</th>
                <th style="width: 150px;">Date</th>
                <th>Amount</th>
                <th style="width: 70px;">Fee Amt</th>
                <th style="width: 50px;">Fee %</th>
                <th style="width: 200px;">Product Name</th>
                <th style="width: 150px;">Card Holder Name</th>
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
  <!-- Include DataTables CSS and JavaScript files -->
  <!-- Include DataTables CSS -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">

  <!-- Include DataTables FixedColumns CSS -->
  <link rel="stylesheet" href="https://cdn.datatables.net/fixedcolumns/4.3.0/css/fixedColumns.dataTables.min.css">

  <!-- Include DataTables JavaScript -->
  <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

  <!-- Include Clipboard JavaScript -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.8/clipboard.min.js"></script>

  <!-- Include Date and time format JavaScript -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>

  <!-- Include DataTables FixedColumns JavaScript -->
  <script src="https://cdn.datatables.net/fixedcolumns/4.3.0/js/dataTables.fixedColumns.min.js"></script>

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
      // Initialize DataTable
      let dataTable = $('#reportTable').DataTable({
        searching: false, // Hide the search menu
        processing: true,
        serverSide: true,
        fixedColumns: {
          left: 2,
          right: 2
        },
        scrollCollapse: true,
        scrollX: true,
        columnDefs: [
          { targets: '_all', orderable: false } // Disable sorting for all columns
        ],
        ajax: {
          url: window.location.origin + '/api/getReport',
          data: function (d) {
            d.orderNo = $('#orderNo').val();
            d.fromDate = $('#fromDate').val();
            d.toDate = $('#toDate').val();
            d.email = $('#email').val();
            d.name = $('#name').val();
            d.status = $('#status').val();
            d.pageNo = (parseInt(d.start / d.length) + 1); // Calculate page number
            d.pageSize = d.length;
          },
          dataSrc: function (response) {
            var formattedJSON = JSON.stringify(response, null, 2);
            $('.error-box').hide();
            $(".api-response").removeClass('text-danger').removeClass('text-success').addClass('text-success');
            $(".api-response").html('<pre>' + formattedJSON + '</pre>');

            // Map the expected keys
            response.recordsTotal = response.body.total_data_size;
            response.recordsFiltered = response.body.searched_data_size;

            return response.body.data;
          },
          beforeSend: function (xhr) {
            xhr.setRequestHeader('Authorization', API_KEY);
          },
          error: function(jqXHR, textStatus, errorThrown) {
            $('.error-box').show();
            $('.error-box .card-header').html("Error");
            $('.error-box .card-body').html(jqXHR.responseJSON.message);
            $(".api-response").removeClass('text-danger').removeClass('text-success').addClass('text-danger');
            return null;
          }
        },
        columns: [
          {
            data: null,
            render: function (data, type, row, meta) {
              return meta.row + 1 + (meta.settings._iDisplayStart || 0);
            }
          },
          {
            data: 'orderNo',
            render: function (data, type, row, meta) {
              if (type === 'display') {
                return data === null ? '' : data;
              }
              return data;
            },
          },
          {
            data: 'name',
            render: function (data, type, row, meta) {
              if (type === 'display') {
                return data === null ? '' : data;
              }
              return data;
            },
          },
          {
            data: 'email_address',
            render: function (data, type, row, meta) {
              if (type === 'display') {
                return data === null ? '' : data;
              }
              return data;
            },
          },
          {
            data: 'created_at',
            render: function (data, type, row, meta) {
              if (type === 'display') {
                return data === null ? '' : convertDateString(data);
              }
              return data;
            },
          },
          {
            data: 'amount',
            render: function (data, type, row, meta) {
              if (type === 'display') {
                return (data === null || data*1 === 0) ? '' : data;
              }
              return data;
            },
          },
          {
            data: 'fee',
            render: function (data, type, row, meta) {
              if (type === 'display') {
                return (data === null || data*1 === 0) ? '' : data;
              }
              return data;
            },
          },
          {
            data: 'fee_percent',
            render: function (data, type, row, meta) {
              if (type === 'display') {
                return (data === null || data*1 === 0) ? '' : data;
              }
              return data;
            },
          },
          {
            data: 'product_name',
            render: function (data, type, row, meta) {
              if (type === 'display') {
                return data === null ? '' : data;
              }
              return data;
            },
          },
          {
            data: 'card_holder_name',
            render: function (data, type, row, meta) {
              if (type === 'display') {
                return data === null ? '' : data;
              }
              return data;
            },
          },
          {
            data: 'status',
            render: function (data, type, row, meta) {
              if (type === 'display') {
                return data === null ? '' : data;
              }
              return data;
            },
          },
          {
            data: 'domain',
            render: function (data, type, row, meta) {
              if (type === 'display') {
                return data === null ? '' : data;
              }
              return data;
            },
          },
        ],
        // Use the new key names for total records and filtered records
        recordsTotal: 'total_data_size',
        recordsFiltered: 'searched_data_size',
      });

      // Function to trigger DataTable search
      function searchReport() {
        dataTable.ajax.reload();
      }

      // Attach the searchReport function to the button click event
      $('.get-report').on('click', function () {
        searchReport();
      });

      // Error handler
      dataTable.on('error.dt', function(e, settings, techNote, message) {
        $('.error-box').show();
        $('.error-box .card-header').html(message);
        $('.error-box .card-body').html(message);
        $(".api-response").removeClass('text-danger').removeClass('text-success').addClass('text-danger');
      });
    });
  </script>
@endsection
