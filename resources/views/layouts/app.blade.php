<!DOCTYPE html>
<html lang="en">
<head>
  <title>{{ config('app.name', 'Kaiser') }}</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <!-- Add Bootstrap CSS link -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('css/my.css', config('env') == 'local') }}">

  <!-- Add Bootstrap JS and Popper.js (required for some Bootstrap components) -->
  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <!-- Popper -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.9.2/umd/popper.min.js"></script>
  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</head>
<body>
<header class="text-center py-4">
  <h1 class="mb-0">{{ config('app.name', 'Kaiser') }} API</h1>
</header>

<nav class="navbar navbar-expand-lg navbar-light bg-light">
  <div class="container">
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ml-auto">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">JDB Payment</a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="{{ route('page.payment') }}">JDB pre-Payment API</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="{{ route('page.report') }}">Get JDB Payment Report API</a></li>
          </ul>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="{{ route('page.apikey.dashboard') }}">API Keys</a>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">Crypto Payment</a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="{{ route('page.fireblocks.showGetAddressPage') }}">Get Crypto Payment Address API</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="{{ route('page.fireblocks.showReportPage') }}">Get Crypto Payment Report API</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="{{ route('page.fireblocks.showCronJobPage') }}">Check Address Monitoring App</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="{{ route('page.fireblocks.test') }}">Fireblocks API Test</a></li>
          </ul>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="{{ route('admin.resetPasswordPage') }}">Reset Password</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="{{ route('admin.logout') }}">Logout</a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<main>
  <div class="container mt-3">
    @yield('content')
  </div>
</main>

<footer class="bg-light p-3 mt-4">
  <!-- Your footer content here -->
</footer>


</body>
</html>
