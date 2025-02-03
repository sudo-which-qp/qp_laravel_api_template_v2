<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckUserIsSuspended
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        $mUser = User::where('id', $user->id)->first();
        if (is_null($mUser)) {
            return response()->json([
                'status' => 404,
                'success' => false,
                'message' => 'This account does not exist.',
                'data' => null,
            ], 404,);
        }

        if ($mUser->is_suspended === 1) {
            return response()->json([
                'status' => 401,
                'success' => false,
                'message' => 'This account is suspended',
                'data' => null,
            ], 401,);
        }

        return $next($request);
    }
}