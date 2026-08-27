<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaffLoginSelfieCaptured
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->requiresLoginSelfie()
            && ! $request->session()->has('staff_selfie_attendance_id')
            && ! $request->routeIs('staff.selfie.*')) {
            return redirect()->route('staff.selfie.create');
        }

        return $next($request);
    }
}
