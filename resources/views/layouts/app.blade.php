<!DOCTYPE html>
<html lang="en">
<head>
  <title>{{ config('app.name', 'Kaiser') }}</title>
  <!-- Add Bootstrap CSS link -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>
</html>
