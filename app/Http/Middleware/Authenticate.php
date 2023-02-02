<?php

namespace App\Http\Middleware;

use App\Http\Helpers\ResponseGenerator;
use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        if (! $request->expectsJson()) {
            print("Token no detectado, por favor inicia sesión.");
            die();
        }
    }
}
