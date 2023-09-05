@extends('layouts.app')

@section('content')
  <div class="row justify-content-center">
    <div class="col-md-6">
      <div class="error-box card border-danger mb-3" style="display: none">
        <div class="card-header bg-danger text-white">Payment Unsuccessful</div>
        <div class="card-body text-danger">
          <p>Payment was unsuccessful.</p>
        </div>
      </div>
      <div class="card">
        <div class="card-header">Payment</div>
        <div class="card-body">
          <form>
            @csrf
            <div class="mb-3">
              <label for="amount" class="form-label">Amount</label>
              <input type="text" class="form-control" id="amount" name="amount" placeholder="Enter amount">
            </div>
            <div class="mb-3">
              <label for="product_name" class="form-label">Product Name</label>
              <input type="text" class="form-control" id="product_name" name="product_name" placeholder="Enter product name">
            </div>
            <button type="button" class="pre-payment btn btn-primary">Payment</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  <script>
    $(document).ready(function(){
      $('.error-box').hide();

      $('.pre-payment').click(function (){
        $('.error-box').hide();

        let dataToSend = {
          amount: $('#amount').val(),
          product_name: $('#product_name').val(),
        };

        $.ajax({
          url: window.location.origin + '/api/prepayment',
          type: 'POST',
          data: JSON.stringify(dataToSend),
          contentType: 'application/json',
          headers: {
            'Authorization': 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpZCI6IjMiLCJuYW1lIjoiS0FJU0VSIiwiZG9tYWluIjoiQVBJLktBSVNFUlBBWU1FTlQuQ09NIiwiZmVlIjoiNiIsImV4cCI6NDg0OTU0NjM3MH0.TXhyJpy_33X7HdrMvJsYwEtGLeU4zvOTvLA2Wg-NLdI'
          },
          success: function(response) {
            if( response['code'] !== 200 ){
              $('.error-box').show();
              $('.error-box .card-header').html(response['message']);
              $('.error-box .card-body').html(response['body']);
            }
            else{
              window.location.href = response['body'];
            }
          },
          error: function(xhr, textStatus, errorThrown) {
            console.log(xhr.responseJSON);
            $('.error-box').show();
            $('.error-box .card-header').html(xhr.responseJSON.message);
            $('.error-box .card-body').html(xhr.responseJSON.body);
          }
        });
      })
    });
  </script>
@endsection
