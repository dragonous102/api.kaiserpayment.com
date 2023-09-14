@extends('layouts.app')

@section('content')
  <div class="row justify-content-center">
    <div class="col-md-6 mt-3">
      <h5 class="text-secondary mb-3">Address Scanning Application Status</h5>
      <div class="card">
        <div class="card-header">
          <span class="{{ $cronJobStatus === 'running' ? 'text-success' : 'text-danger' }}">
              {{ $cronJobStatus === 'running' ? 'Running (The Scanning App is running)' : 'Stopped (The Scanning App is not running)' }}
          </span>
        </div>
        <div class="card-body">
          <div class="mb-3 row">
            <label class="col-md-4">Last Execution Time:</label>
            <p class="col-md-8">{{ $lastExecutedTime }}</p>
          </div>
          <div class="mb-3 row">
            <label class="col-md-4">Last Execution Duration:</label>
            <p class="col-md-8"><span style="font-size: xx-large">{{ $executionDuration }}</span> seconds</p>
          </div>
          <div class="mb-3 row">
            <label class="col-md-4">Status:</label>
            <p class="col-md-8">
              <span class="badge {{ $cronJobStatus === 'running' ? 'bg-success' : 'bg-danger' }}">
                  {{ $cronJobStatus === 'running' ? 'Running' : 'Stopped' }}
              </span>
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
