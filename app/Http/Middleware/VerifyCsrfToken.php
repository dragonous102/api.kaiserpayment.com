<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * Indicates whether the XSRF-TOKEN cookie should be set on the response.
     *
     * @var bool
     */
    protected $addHttpCookie = true;

    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        "/upload/csvFile",
        "/set-2Fa",
        "/disable-2Fa",
        "withdraw",
        "buy-with-card",
        "withdraw-verify-code",
        "buy-verify-code"
        //
    ];
}
