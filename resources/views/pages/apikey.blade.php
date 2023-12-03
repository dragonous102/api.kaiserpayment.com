@extends('layouts.app')

@section('content')
  <div class="container">
    <div class="row justify-content-center">
      <div class="add-success error-box card border-success mb-3" style="display: none">
        <div class="card-body text-success"></div>
      </div>
      <div class="add-failed error-box card border-danger mb-3" style="display: none">
        <div class="card-body text-danger"></div>
      </div>
    </div>
    <table class="table">
      <thead>
      <tr>
        <th>Partner Name</th>
        <th>Partner Domain</th>
        <th>Fee(%)</th>
        <th>Crypto<br>Fee(%)</th>
        <th style="width: 80px;">API Key</th>
        <th style="width: 100px;">Status</th>
        <th style="width: 100px;">Service<br/>Type</th>
        <th>Update Date</th>
        <th>Operation</th>
      </tr>
      <tr>
        <td>
          <input type="text" class="form-control" id="new_name" placeholder="New Partner Name">
        </td>
        <td>
          <input type="text" class="form-control" id="new_domain" placeholder="New.example.com">
        </td>
        <td>
          <input type="number" class="form-control" id="new_fee" placeholder="fee %" style="width: 100px;">
        </td>
        <td>
          <input type="number" class="form-control" id="new_crypto_fee" placeholder="crypto fee %" style="width: 100px;">
        </td>
        <td>
          <!-- API Key (Static) -->
        </td>
        <td>
          <select class="form-control" id="new_status">
            <option value="1">Enable</option>
            <option value="0">Disable</option>
          </select>
        </td>
        <td>
          <select class="form-control" id="new_service_type">
            <option value="UAT">UAT</option>
            <option value="PROD">PROD</option>
          </select>
        </td>
        <td></td>
        <td style="width: 310px;">
          <button class="btn btn-primary" onclick="addPartner()">
            <i class="fas fa-plus-circle"></i>
            Add New Partner</button>
        </td>
      </tr>
      </thead>
      <tbody id="partner-body">
      <!-- Loop through your data here -->
      @foreach ($partners as $partner)
        <tr>
          <td class="name">{{ $partner->name }}</td>
          <td class="domain">{{ $partner->domain }}</td>
          <td class="fee">{{ $partner->fee }}</td>
          <td class="crypto_fee">{{ $partner->crypto_fee }}</td>
          <td class="api-key">
            <button class="trans-btn" data-bs-toggle="modal" data-bs-target="#apiKeyModal" onclick="getApiKey({{ $partner->id }})">
              @if ($partner->api_key == 'MISSING' )
                <i class="fas fa-eye-slash"></i>
              @elseif($partner->api_key == 'INVALID' )
                <i class="fas fa-eye text-danger"></i>
              @else
                <i class="fas fa-eye text-success"></i>
              @endif
            </button>
          </td>
          <td class="status">
            @if ($partner->status == 1)
              <span class="text-success">
                <i class="fas fa-check-circle"></i> Enabled
              </span>
            @else
              <span class="text-danger">
                <i class="fas fa-ban"></i> Disabled
              </span>
            @endif
          </td>
          <td class="service_type">
            @if ($partner->service_type == 'UAT')
              <span class="text-warning-emphasis">
                  <i class="fas fa-user-circle large-icon"></i>
              </span>
            @elseif ($partner->service_type == 'PROD')
              <span class="text-success">
                  <i class="fab fa-product-hunt large-icon"></i>
              </span>
            @else
              <span class="text-danger">
                <i class="fas fa-exclamation-triangle"></i>
              </span>
            @endif
            &nbsp{{ $partner->service_type }}
          </td>
          <td class="update_at">{{ $partner->updated_at }}</td>
          <td>
            <button class="btn btn-outline-success" onclick="applyApiKey({{ $partner->id }})">
              <i class="fas fa-check"></i> Apply Api Key
            </button>
            <button class="btn btn-outline-primary" id="modal_update_{{ $partner->id }}" data-bs-toggle="modal" data-bs-target="#updatePartnerModal" onclick="getPartner({{ $partner->id }})">
              <i class="fas fa-edit"></i> Update
            </button>
            <button class="btn btn-danger" onclick="deletePartner({{ $partner->id }})">
              <i class="fas fa-trash-alt"></i>
            </button>
          </td>
        </tr>
      @endforeach

      @include('pages.apikey_modal')
      @include('pages.apikey_modal_api')

      </tbody>
      <tfoot>

      </tfoot>
    </table>
  </div>
  <script src="{{ asset('js/http_code.jquery.com_jquery-3.6.0.js', config('env') == 'local') }}"></script>
  <script>
    $(document).ready(function(){
    });

    function addPartner(){
      let dataToSend = {
        name: $('#new_name').val(),
        domain: $('#new_domain').val(),
        fee: $('#new_fee').val(),
        crypto_fee: $('#new_crypto_fee').val(),
        status: $('#new_status').val(),
        service_type: $('#new_service_type').val(),
      };

      $.ajax({
        url: window.location.origin + '/admin/apikey-add-partner',
        type: 'POST',
        data: JSON.stringify(dataToSend),
        contentType: 'application/json',
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
          if( response['code'] !== 200 ){
            $('.add-success').hide();
            $('.add-failed').show();
            $('.add-failed .card-body').html(response['message']);
          }
          else{
            $('.add-success').show();
            $('.add-failed').hide();
            $('.add-success .card-body').html(response['message']);

            let body = response['body'];
            let status = '<span class="text-success">\
                              <i class="fas fa-check-circle"></i> Enabled\
                          </span>';
            if( body.status * 1 === 0 ){
              status = '<span class="text-danger">\
                            <i class="fas fa-ban"></i> Disabled\
                        </span>'
            }

            let service_type;
            if( body.service_type === 'PROD' ){
              service_type = '<span class="text-success">\
                            <i class="fab fa-product-hunt large-icon"></i>\
                        </span>'
            }
            else if( body.service_type === 'UAT' ){
              service_type = '<span class="text-warning-emphasis">\
                              <i class="fas fa-user-circle large-icon"></i>\
                          </span>';
            }
            else{
              service_type = '<span class="text-danger">\
                              <i class="fas fa-exclamation-triangle"></i>\
                          </span>';
            }

            let newRow = '<tr>\
                        <td class="name">'+ body.name +'</td>\
                        <td class="domain">'+ body.domain +'</td>\
                        <td class="fee">'+ body.fee +'</td>\
                        <td class="crypto_fee">'+ body.crypto_fee +'</td>\
                        <td><button class="trans-btn" onclick="getApiKey('+ body.id +')" data-bs-toggle="modal" data-bs-target="#apiKeyModal">\
                          <i class="fas fa-eye-slash"></i></button></td>\
                        <td class="status">'+ status +'</td>\
                        <td class="service_type">'+ service_type + '&nbsp' + body.service_type +'</td>\
                        <td class="update_at">'+ body.updated_at +'</td>\
                        <td>\
                          <button class="btn btn-outline-success" onclick="applyApiKey('+ body.id +')">\
                              <i class="fas fa-check"></i> Apply Api Key\
                          </button>\
                          <button class="btn btn-outline-primary" id="modal_update_'+ body.id +'" data-bs-toggle="modal" data-bs-target="#updatePartnerModal" onclick="getPartner('+ body.id +')">\
                            <i class="fas fa-edit"></i> Update\
                          </button>\
                          <button class="btn btn-danger" onclick="deletePartner('+ body.id +')">\
                            <i class="fas fa-trash-alt"></i>\
                          </button>\
                        </td>\
                      </tr>';
            $("#partner-body").prepend(newRow);
          }
        },
        error: function(xhr, textStatus, errorThrown) {
          $('.add-success').hide();
          $('.add-failed').show();
          $('.add-failed .card-body').html(xhr.responseJSON.message);
        }
      });
    }

    function getPartner(id){

      $('.modal-success').hide();
      $('.modal-failed').hide();

      let dataToSend = {
        id: id,
      };

      $.ajax({
        url: window.location.origin + '/admin/apikey-get-partner',
        type: 'POST',
        data: JSON.stringify(dataToSend),
        contentType: 'application/json',
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
          if( response['code'] !== 200 ){
            $('.add-success').hide();
            $('.add-failed').show();
            $('.add-failed .card-body').html(response['message']);
          }
          else{
            $('.add-failed').hide();
            let data = response['body'];
            $('#modal_id').val(data.id);
            $('#modal_name').val(data.name);
            $('#modal_domain').val(data.domain);
            $('.update-partner').text(data.name + ' (' + data.domain + ')');
            $('#modal_fee').val(data.fee);
            $('#modal_crypto_fee').val(data.crypto_fee);
            $('#modal_status').val(data.status);
            $('#modal_service_type').val(data.service_type);
            $('#modal_api_key').text(data.api_key);
          }
        },
        error: function(xhr, textStatus, errorThrown) {
          $('.add-success').hide();
          $('.add-failed').show();
          $('.add-failed .card-body').html(xhr.responseJSON.message);
        }
      });
    }

    function deletePartner(id){
      $('.modal-success').hide();
      $('.modal-failed').hide();

      let confirmed = window.confirm('Are you sure you want to delete this partner?');
      if (!confirmed)
        return;

      let dataToSend = {
        id: id,
      };

      $.ajax({
        url: window.location.origin + '/admin/apikey-delete-partner',
        type: 'POST',
        data: JSON.stringify(dataToSend),
        contentType: 'application/json',
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
          if( response['code'] !== 200 ){
            $('.add-success').hide();
            $('.add-failed').show();
            $('.add-failed .card-body').html(response['message']);
          }
          else{
            $('.add-failed').hide();
            $('.add-success').show();
            $('.add-success .card-body').html(response['message']);
            $("#modal_update_" + id).closest('tr').remove();
          }
        },
        error: function(xhr, textStatus, errorThrown) {
          $('.add-success').hide();
          $('.add-failed').show();
          $('.add-failed .card-body').html(xhr.responseJSON.message);
        }
      });
    }

    function getApiKey( id, other){

      let dataToSend = {
        id: id,
      };

      $.ajax({
        url: window.location.origin + '/admin/apikey-get-partner',
        type: 'POST',
        data: JSON.stringify(dataToSend),
        contentType: 'application/json',
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
          if( response['code'] !== 200 ){
            $('.update-partner-apikey').text('');
          }
          else{
            let data = response['body'];
            $('.update-partner-apikey').text(data.name + ' (' + data.domain + ')');
          }
        },
        error: function(xhr, textStatus, errorThrown) {
        }
      });

      $.ajax({
        url: window.location.origin + '/admin/apikey-get-apikey',
        type: 'POST',
        data: JSON.stringify(dataToSend),
        contentType: 'application/json',
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
          if( response['code'] === 200 ){
            let $transBtn = $('button#modal_update_' + id).closest('tr').find('.trans-btn');
            $transBtn = $transBtn.find('i').removeClass('fa-eye-slash').removeClass('fa-eye').removeClass('text-danger').removeClass('text-success');
            if( response['body'].msg === 'INVALID_API_KEY'){
              $transBtn = $('button#modal_update_' + id).closest('tr').find('.trans-btn');
              $transBtn.find('i').addClass('fa-eye').addClass('text-danger');
              $(".modal-api-key").html(response['message'] + "<p class='text-danger'>" + response['body'].api_key) + "</p>";
              if(other === true){
                $('.add-success').hide();
                $('.add-failed').show();
                $('.add-failed .card-body').html(response['message']);
              }
            }
            else if( response['body'].msg === 'VALID_API_KEY'){
              $transBtn = $('button#modal_update_' + id).closest('tr').find('.trans-btn');
              $transBtn.find('i').addClass('fa-eye').addClass('text-success');
              $(".modal-api-key").html(response['message'] + "<p class='text-success'>" + response['body'].api_key) + "</p>";
            }
            else if( response['body'].msg === 'NO_API_KEY'){
              $transBtn = $('button#modal_update_' + id).closest('tr').find('.trans-btn');
              $transBtn.find('i').addClass('fa-eye-slash');
              $(".modal-api-key").html(response['message']);
              if(other === true){
                $('.add-success').hide();
                $('.add-failed').show();
                $('.add-failed .card-body').html(response['message']);
              }
            }
            else{
              $(".modal-api-key").html(response['message'] + response['body'].api_key);
            }
          }
        },
        error: function(xhr, textStatus, errorThrown) {
          $('.add-success').hide();
          $('.add-failed').show();
          $('.add-failed .card-body').html(xhr.responseJSON.message);
        }
      });
    }

    function applyApiKey(id){

      $('.add-success').hide();
      $('.add-failed').hide();

      let dataToSend = {
        id: id,
      };

      $.ajax({
        url: window.location.origin + '/admin/apikey-apply-apikey',
        type: 'POST',
        data: JSON.stringify(dataToSend),
        contentType: 'application/json',
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
          if( response['code'] === 200 ){
            $('.add-success').show();
            $('.add-failed').hide();
            $('.add-success .card-body').html(response['message']);
            getApiKey(id, true);
          }
        },
        error: function(xhr, textStatus, errorThrown) {
          $('.add-success').hide();
          $('.add-failed').show();
          $('.add-failed .card-body').html(xhr.responseJSON.message);
        }
      });
    }
  </script>
@endsection
