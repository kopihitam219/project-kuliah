<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;

class LogoutResponse implements LogoutResponseContract
{
    /**
     * Create an HTTP response when the user logs out.
     */
    public function toResponse($request)
    {
        return redirect()->route('login');
    }
}