<!DOCTYPE html>
<html lang="en">
<head>
  <title>{{ config('app.name', 'Kaiser') }}</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <!-- Add Bootstrap CSS link -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('css/my.css', config('env') == 'local') }}">
  <style>
    /* CSS to make text unselectable */
    .unselectable {
      -webkit-user-select: none; /* Safari */
      -moz-user-select: none; /* Firefox */
      -ms-user-select: none; /* IE10+/Edge */
      user-select: none; /* Standard */
    }
  </style>
</head>
<body>


<!-- Modal -->
<div class="modal fade unselectable" tabindex="-1" data-bs-backdrop="static" role="dialog" id="loginModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">{{ config('app.name', 'Kaiser') }} Sign in</h5>
      </div>
      <form action="{{ route('admin.login') }}" method="post">
        @csrf
        <div class="modal-body">
          <div class="form-group" style="margin: 0 50px 16px 50px;">
            <input type="text" class="form-control" id="name" name="name" placeholder="Username">
          </div>
          <div class="form-group" style="margin: 0 50px 0 50px;">
            <input type="password" class="form-control" id="password" name="password" placeholder="Password">
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary" id="loginButton"><i class="fas fa-sign-in-alt"></i>&nbsp&nbsp&nbspSign in</button>
          <button type="button" class="btn btn-outline-secondary" data-dismiss="modal" onclick="cancelLogin();">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Add Bootstrap JS and jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>

<!-- JavaScript to trigger the modal on page load -->
<script>
  $(document).ready(function () {
    $(document).on('contextmenu', function (e) {
      e.preventDefault();
    });

    // Show the modal on page load
    $('#loginModal').modal('show');

    // Set focus to the username input field
    $('#loginModal').on('shown.bs.modal', function () {
      $('#username').focus();
    });
  });

  function cancelLogin(){
    window.location.reload();
  }
</script>

</body>
</html>
