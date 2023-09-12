@extends('layouts.app')

@section('content')
  <div class="row justify-content-center">
    <h5 class="text-secondary mb-3">https://api.kaiserpayment.com/api/prepayment</h5>
    <div class="row">
      <div class="error-box card border-danger mb-3 col-md-12" style="display: none">
        <div class="card-header bg-danger text-white" style="display: none;">Payment Unsuccessful</div>
        <div class="card-body text-danger">
          <p>Payment was unsuccessful.</p>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card">
        <div class="card-header">Parameters of JDB pre-Payment API</div>
        <div class="card-body" style="height: 220px;">
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
          </form>
        </div>
        <div class="card-footer">
          <button type="button" class="pre-payment btn btn-primary">Call API</button>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card">
        <div class="card-header">API Response</div>
        <div class="card-body api-response" style="height: 220px;">
        </div>
        <div class="card-footer" align="right">
          <a type="button" class="redirect-btn btn btn-success">Redirect</a>
        </div>
      </div>
    </div>
  </div>

  <script src="{{ asset('js/http_code.jquery.com_jquery-3.6.0.js', config('env') == 'local') }}"></script>

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
            'Authorization': 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpZCI6IjEiLCJuYW1lIjoiS0FJU0VSIiwiZG9tYWluIjoiQVBJLktBSVNFUlBBWU1FTlQuQ09NIiwiZmVlIjoiNyIsImV4cCI6NDg0OTU3OTQxNH0.Tqlf_hxqvYu9u-Qw4pUMdHV507CZm48HUnVvfxC8DsQ'
          },
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
              $('.redirect-btn').attr('href', response['body']);
              $(".api-response").removeClass('text-danger').removeClass('text-success').addClass('text-success');
              //window.location.href = response['body'];
            }
          },
          error: function(xhr, textStatus, errorThrown) {
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
