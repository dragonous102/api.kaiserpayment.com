<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Support\Facades\Log;
class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function redirectTo($request)
    {
      Log::info('Request Route URL in Authenticate Middleware: ' . $request->path());
        if (! $request->expectsJson()) {
          Log::info('Redirecting to login page');
            return route('page.login');
        }
    }
}
