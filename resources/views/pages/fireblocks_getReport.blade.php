@extends('layouts.app')

@section('content')
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header">Report</div>
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
                        <option value="">Select Payment Status</option>
                        <option value="pending">Pending</option>
                        <option value="complete">Complete</option>
                        <option value="over">Over</option>
                      </select>
                    </div>
                  </div>
                  <div class="col-md-2">
                    <div class="form-group">
                      <label for="address">Address</label>
                      <input type="text" class="form-control" id="address" name="address" placeholder="Address">
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <button type="button" id="btnSearch" onclick="searchReport()" class="btn btn-primary mt-3"><i class="fas fa-search"></i> Search</button>
                <button type="button" class="btn btn-success mt-3"><i class="fas fa-download"></i> Download xlsx</button>
              </div>
            </div>
            <hr class="mt-0">
            <div class="row">
              <div class="col-md-12">
                <table class="table">
                  <thead>
                  <tr>
                    <th>No</th>
                    <th>Currency</th>
                    <th>Payment Amount</th>
                    <th>Wallet Balance</th>
                    <th>Payment Status</th>
                    <th>Fee</th>
                    <th>Status</th>
                    <th>Date</th>
                  </tr>
                  </thead>
                  <tbody id="searchResults">
                  <!-- Search results will be displayed here -->
                  </tbody>
                </table>
                <nav aria-label="Page navigation">
                  <ul class="pagination justify-content-end" id="pagination">
                    <!-- Pagination links will be added here -->
                  </ul>
                </nav>
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
    // Function to handle the search request and update the result table and pagination
    function searchReport() {
      // Perform the AJAX request to fetch the search results
      // Update the 'searchResults' and 'pagination' elements with the results
      // Example AJAX request:
      $.ajax({
        url: '/your-api-endpoint', // Replace with your API endpoint
        type: 'POST',
        data: {
          order_id: $('#order_id').val(),
          from_date: $('#from_date').val(),
          to_date: $('#to_date').val(),
          payment_status: $('#payment_status').val(),
          address: $('#address').val(),
          page: 1, // Example page number
          page_size: 10, // Example page size
        },
        success: function(response) {
          // Update the 'searchResults' and 'pagination' elements here
          // Example:
          $('#searchResults').html(response.resultsHtml);
          $('#pagination').html(response.paginationHtml);
        },
        error: function(xhr, textStatus) {
          console.log('Error:', xhr.responseJSON.message);
        }
      });
    }

    // Call the initial search when the page loads
    searchReport();
  </script>
@endsection
