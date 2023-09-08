<!DOCTYPE html>
<html lang="en">
<head>
  <title>{{ config('app.name', 'Kaiser') }}</title>
  <!-- Add Bootstrap CSS link -->
  <link rel="stylesheet" href="{{ asset('css/http_cdn.jsdelivr.net_npm_bootstrap@5.3.0_dist_css_bootstrap.css', config('env') == 'local') }}">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('css/my.css', config('env') == 'local') }}">
  <meta name="csrf-token" content="{{ csrf_token() }}">
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
        <li class="nav-item">
          <a class="nav-link" href="{{ route('page.payment') }}">Payment</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="{{ route('page.report') }}">Report</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="{{ route('page.admin.dashboard') }}">Admin</a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<main>
  <div class="container mt-4">
    @yield('content')
  </div>
</main>

<footer class="bg-light p-3 mt-4">
  <!-- Your footer content here -->
</footer>

<!-- Add Bootstrap JS and Popper.js (required for some Bootstrap components) -->
<!-- jQuery -->
<script src="{{ asset('js/http_code.jquery.com_jquery-3.6.0.js', config('env') == 'local') }}"></script>

<!-- Bootstrap JS -->
<script src="{{ asset('js/http_cdn.jsdelivr.net_npm_bootstrap@5.3.0_dist_js_bootstrap.js', config('env') == 'local') }}"></script>
</body>
</html>
