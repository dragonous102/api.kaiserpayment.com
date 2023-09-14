@extends('layouts.app')

@section('content')
  <div class="container">
    <h5 class="text-secondary mb-3">https://api.kaiserpayment.com/api/getCryptoPaymentReport</h5>
    <div class="row justify-content-center">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header">Parameters of Get Crypto Payment Report API</div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-9">
                <div class="row">
                  <div class="col-md-2">
                    <div class="form-group">
                      <label for="order_id">Order Id</label>
                      <input type="text" class="form-control" id="order_id" name="order_id" placeholder="Order Id">
                    </div>
                  </div>
                  <div class="col-md-2">
                    <div class="form-group">
                      <label for="from_date">From Date</label>
                      <input type="text" class="form-control" id="from_date" name="from_date" placeholder="YYYY-MM-DD">
                    </div>
                  </div>
                  <div class="col-md-2">
                    <div class="form-group">
                      <label for="to_date">To Date</label>
                      <input type="text" class="form-control" id="to_date" name="to_date" placeholder="YYYY-MM-DD">
                    </div>
                  </div>
                  <div class="col-md-2">
                    <div class="form-group">
                      <label for="payment_status">Payment Status</label>
                      <select class="form-control" id="payment_status" name="payment_status">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="complete">Complete</option>
                        <option value="over">Over</option>
                      </select>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label for="address">Address</label>
                      <input type="text" class="form-control" id="address" name="address" placeholder="Address">
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <button type="button" id="btnSearch" class="btn btn-primary mt-4"><i class="fas fa-search"></i> API Call</button>
                <button type="button" class="btn btn-success mt-4"><i class="fas fa-download"></i> Download xlsx</button>
              </div>
            </div>
            <hr class="mt-0">
            <div class="row">
              <div class="col-md-12">
                <table class="table" id="reportTable">
                  <thead>
                  <tr>
                    <th>No</th>
                    <th>Name</th>
                    <th>Order ID</th>
                    <th>Currency</th>
                    <th>Payment<br>Amount</th>
                    <th>Wallet<br>Balance</th>
                    <th>Address</th>
                    <th>Payment<br>Status</th>
                    <th>Fee</th>
                    <th>Status</th>
                    <th>Date</th>
                  </tr>
                  </thead>
                  <tbody>
                  <!-- Search results will be displayed here -->
                  </tbody>
                </table>
              </div>
            </div>
          </div>
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

  <!-- Include DataTables CSS and JavaScript files -->
  <!-- Include DataTables CSS -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">

  <!-- Include DataTables JavaScript -->
  <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

  <!-- Include Clipboard JavaScript -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.8/clipboard.min.js"></script>

  <!-- Include Date and time format JavaScript -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>

  <script>
    $(document).ready(function(){
      // Initialize DataTable
      let dataTable = $('#reportTable').DataTable({
        searching: false, // Hide the search menu
        processing: true,
        serverSide: true,
        columnDefs: [
          { targets: '_all', orderable: false } // Disable sorting for all columns
        ],
        ajax: {
          url: window.location.origin + '/api/getCryptoPaymentReport',
          data: function (d) {
            d.orderId = $('#order_id').val();
            d.fromDate = $('#from_date').val();
            d.toDate = $('#to_date').val();
            d.address = $('#address').val();
            d.paymentStatus = $('#payment_status').val();
            d.pageNo = (parseInt(d.start / d.length) + 1); // Calculate page number
            d.pageSize = d.length;
          },
          dataSrc: function (response) {
            var formattedJSON = JSON.stringify(response, null, 2);
            $(".api-response").html('<pre>' + formattedJSON + '</pre>');

            // Map the expected keys
            response.recordsTotal = response.body.total_data_size;
            response.recordsFiltered = response.body.searched_data_size;

            return response.body.data;
          },
          beforeSend: function (xhr) {
            xhr.setRequestHeader('Authorization', 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpZCI6IjEiLCJuYW1lIjoiS0FJU0VSIiwiZG9tYWluIjoiQVBJLktBSVNFUlBBWU1FTlQuQ09NIiwiZmVlIjoiNyIsImV4cCI6NDg0OTU3OTQxNH0.Tqlf_hxqvYu9u-Qw4pUMdHV507CZm48HUnVvfxC8DsQ');
          },
        },
        columns: [
          {
            data: null,
            render: function (data, type, row, meta) {
              return meta.row + 1 + (meta.settings._iDisplayStart || 0);
            }
          },
          { data: 'partner_name' },
          { data: 'order_id' },
          { data: 'currency' },
          {
            data: 'payment_amount',
            render: function (data, type, row, meta) {
              if (type === 'display') {
                return data === "0" ? '' : data;
              }
              return data;
            },
          },
          {
            data: 'wallet_balance',
            render: function (data, type, row, meta) {
              if (type === 'display') {
                return data === "0" ? '' : data;
              }
              return data;
            },
          },
          {
            data: 'address',
            render: function (data, type, row, meta){
              if (type === 'display') {
                if (data !== null) {
                  const shortAddress = data.substring(0, 6) + '...';
                  const copyIcon = `<i class="far fa-copy copy-address" title="copy address" data-clipboard-text="${data}" id="copy-icon-${meta.row}"></i>`;
                  return `${shortAddress} <div class="float-right copy-address-icon">${copyIcon}</div>`;
                }
                return '';
              }
              return data;
            }
          },
          {
            data: 'payment_status',
            render: function (data, type, row, meta) {
              if (type === 'display') {
                let badgeClass = '';
                let text = '';

                switch (data) {
                  case 'complete':
                    badgeClass = 'bg-success';
                    text = '<i class="fas fa-check"></i> Complete';
                    break;
                  case 'pending':
                    badgeClass = 'bg-info';
                    text = 'Pending...';
                    break;
                  case 'over':
                    badgeClass = 'bg-danger';
                    text = 'Over';
                    break;
                  default:
                    badgeClass = '';
                    text = data;
                    break;
                }

                return `<span class="badge ${badgeClass}">${text}</span>`;
              }
              return data;
            },
          },
          {
            data: 'fee',
            render: function (data, type, row, meta) {
              if (type === 'display') {
                return data === "0" ? '' : data;
              }
              return data;
            },
          },
          {
            data: 'status',
            render: function (data, type, row, meta) {
              if (type === 'display') {
                let textClass = '';
                let icon = '<i class="fas fa-check"></i>';

                switch (data) {
                  case 'success':
                    textClass = 'text-success';
                    break;
                  case 'failed':
                    textClass = 'text-danger';
                    icon = '<i class="fas fa-exclamation-triangle"></i>';
                    break;
                  default:
                    textClass = '';
                    icon = '<i class="fas fa-exclamation-triangle"></i>';
                    break;
                }

                return `<span class="${textClass}">${icon} ${data}</span>`;
              }
              return data;
            },
          },
          { data: 'updated_at', render: function (data, type, row, meta){
              if (type === 'display') {
                return moment(data).format('YY/M/D H:m:s');
              }
              return data;
            } },
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
      $('#btnSearch').on('click', function () {
        searchReport();
      });
    });

    // Initialize Clipboard.js
    const clipboard = new ClipboardJS('.copy-address', {
      text: function (trigger) {
        // This function returns the text to be copied when the copy icon is clicked.
        return trigger.getAttribute('data-clipboard-text');
      },
    });

    // Handle successful copying
    clipboard.on('success', function (e) {
      e.clearSelection();
      // You can add any custom logic here when copying is successful.
      //alert('Address copied to clipboard!');
    });

    // Handle copying errors
    clipboard.on('error', function (e) {
      // You can add any custom logic here when copying encounters an error.
      console.error('Copying failed:', e.action);
    });
  </script>
@endsection
