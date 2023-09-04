@extends('layouts.app')

@section('content')
  <div class="container">
    <table class="table">
      <thead>
      <tr>
        <th>Partner Name</th>
        <th>Partner Domain</th>
        <th>Fee(%)</th>
        <th>API Key</th>
        <th>Status</th>
        <th>Update Date</th>
        <th>Operation</th>
      </tr>
      </thead>
      <tbody>
      <!-- Loop through your data here -->
      @foreach ($adminData as $data)
        <tr>
          <td>{{ $data->name }}</td>
          <td>{{ $data->domain }}</td>
          <td>{{ $data->fee }}</td>
          <td>{{ $data->api_key }}</td>
          <td>
            <select class="form-control" id="status_{{ $data->id }}">
              <option value="enable" {{ $data->status == 'enable' ? 'selected' : '' }}>Enable</option>
              <option value="disable" {{ $data->status == 'disable' ? 'selected' : '' }}>Disable</option>
            </select>
          </td>
          <td>{{ $data->update_date }}</td>
          <td>
            <button class="btn btn-primary" data-toggle="modal" data-target="#updateModal_{{ $data->id }}">Update</button>
          </td>
        </tr>

        @include('pages.admin.modal')

      @endforeach
      </tbody>
      <tfoot>
      <tr>
        <td>
          <input type="text" class="form-control" id="new_name" placeholder="New Partner Name">
        </td>
        <td>
          <input type="text" class="form-control" id="new_domain" placeholder="NewPartner.example.com">
        </td>
        <td>
          <input type="number" class="form-control" id="new_fee" placeholder="fee %">
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
        <td></td>
        <td>
          <button class="btn btn-primary" onclick="addEntry()">Add New Partner</button>
        </td>
      </tr>
      </tfoot>
    </table>
    <div class="row justify-content-center">
      <div class="col-md-6">
        <div class="add-success error-box card border-success mb-3" style="display: none">
          <div class="card-header bg-success text-white">Added new partner successfully.</div>
          <div class="card-body text-success">
            <p>Payment was unsuccessful.</p>
          </div>
        </div>
        <div class="add-failed error-box card border-danger mb-3" style="display: none">
          <div class="card-header bg-danger text-white">Failed to add new partner.</div>
          <div class="card-body text-danger">
            <p>Payment was unsuccessful.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script>
    $(document).ready(function(){

    });

    function addEntry(){
      let dataToSend = {
        name: $('#new_name').val(),
        domain: $('#new_domain').val(),
        fee: $('#new_fee').val(),
        status: $('#new_status').val(),
      };

      $.ajax({
        url: window.location.origin + '/admin-add-partner',
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
