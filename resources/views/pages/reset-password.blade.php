@extends('layouts.app')

@section('content')
  <div class="row justify-content-center">
    <h5 class="text-secondary">Reset Password</h5>
    <div class="col-md-6 mt-3">
      <div class="card">
        <div class="card-body row">
          <form>
            @csrf
            <div class="form-group row mb-3">
              <label for="old_password" class="col-sm-3 form-label">Old Password: </label>
              <div class="col-sm-9">
                <input type="password" class="col-sm-9 form-control" id="old_password" name="old_password"
                       placeholder="Enter old password">
                <span class="text-danger error-message" id="old_password_error"></span>
              </div>
            </div>
            <div class="form-group row mb-3">
              <label for="new_password" class="col-sm-3 form-label">New Password: </label>
              <div class="col-sm-9">
                <input type="password" class="form-control" id="new_password" name="new_password"
                       placeholder="Enter new password">
                <span class="text-danger error-message" id="new_password_error"></span>
              </div>
            </div>
            <div class="form-group row mb-3">
              <label for="new_password_confirmation" class="col-sm-3 form-label">Confirm Password: </label>
              <div class="col-sm-9">
                <input type="password" class="form-control" id="new_password_confirmation" name="new_password_confirmation"
                       placeholder="Confirm new password">
                <span class="text-danger error-message" id="new_password_confirmation_error"></span>
              </div>
            </div>
          </form>
        </div>
        <div class="card-footer" align="right">
          <button type="button" class="reset-password btn btn-primary">Reset Password</button>
          <button type="button" class="btn btn-outline-secondary" onclick="cancelReset();">Cancel</button>
        </div>
      </div>
    </div>
    <br>
    <div class="row"></div>
    <div class="col-md-6 mt-3 resp" style="display: none;">
      <div class="card">
        <div class="card-body reset-response">
        </div>
      </div>
    </div>
  </div>

  <script src="{{ asset('js/http_code.jquery.com_jquery-3.6.0.js', config('env') == 'local') }}"></script>

  <script>
    $(document).ready(function () {

      $('.reset-password').click(function () {
        // Clear previous error messages
        $(".error-message").html("");

        let dataToSend = {
          old_password: $('#old_password').val(),
          new_password: $('#new_password').val(),
          new_password_confirmation: $('#new_password_confirmation').val(),
        };

        $.ajax({
          url: window.location.origin + '/admin/reset-password',
          type: 'POST',
          data: JSON.stringify(dataToSend),
          contentType: 'application/json',
          headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
          },
          success: function (response) {
            $('.resp').show();
            $(".reset-response").html(response.message);
            $(".reset-response").removeClass('text-danger').removeClass('text-success').addClass('text-success');
          },
          error: function (xhr, textStatus, errorThrown) {
            $('.resp').show();
            $(".reset-response").removeClass('text-danger').removeClass('text-success').addClass('text-danger');
            $(".reset-response").html(xhr.responseJSON.message);
            if (xhr.responseJSON.errors) {
              $.each(xhr.responseJSON.errors, function (field, errors) {
                $("#" + field + "_error").html(errors[0]);
              });
            }
          }
        });
      });
    });

    function cancelReset() {
      window.location.reload();
    }
  </script>
@endsection
