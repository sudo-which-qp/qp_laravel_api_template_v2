<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api/v1.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        apiPrefix: 'v1'
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
        /**
         * The application's global HTTP middleware stack.
         *
         * These middleware are run during every request to your application.
         *
         * @var array<int, class-string|string>
         */
        $middleware->append(middleware: [
            \App\Http\Middleware\TrustHosts::class,
            \App\Http\Middleware\TrustProxies::class,
            \Illuminate\Http\Middleware\HandleCors::class,
            \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
            \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
            \App\Http\Middleware\TrimStrings::class,
            \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        ]);

        /**
         * The application's route middleware groups.
         *
         * @var array<string, array<int, class-string|string>>
         */
        $middleware->api(append: [
            // \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            // 'throttle:api',
            \Illuminate\Routing\Middleware\ThrottleRequests::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        /**
         * The application's route middleware.
         *
         * These middleware may be assigned to groups or used individually.
         *
         * @var array<string, class-string|string>
         */
        $middleware->alias(aliases: [
            'auth' => \App\Http\Middleware\Authenticate::class,
            'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
            'auth.session' => \Illuminate\Session\Middleware\AuthenticateSession::class,
            'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
            'can' => \Illuminate\Auth\Middleware\Authorize::class,
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
            'signed' => \App\Http\Middleware\ValidateSignature::class,
            'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
            'cors' => \Illuminate\Http\Middleware\HandleCors::class,

            // my middleware
            'admin' => \App\Http\Middleware\Admin::class,
            'rate_limit' => \App\Http\Middleware\RateLimitMiddleware::class,
            'check_user_verified' => \App\Http\Middleware\CheckUserHasVerify::class,
            'check_user_suspended' => \App\Http\Middleware\CheckUserIsSuspended::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //

        $exceptions->reportable(function (Throwable $e) {
            Log::channel('slack')->error($e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'code' => $e->getCode(),
                'request' => request()->all(),
                'user' => Auth::user(),
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'url' => request()->url(),
                'method' => request()->method(),
                'headers' => request()->headers->all(),
                'trace' => $e->getTraceAsString(),
            ]);
        });

        // Handle unauthenticated requests
        $exceptions->render(function (AuthenticationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 401,
                    'success' => false,
                    'message' => 'You are Unauthenticated.',
                ], 401);
            }
            return redirect()->guest(route('login'));
        });

        // Handle route not found
        $exceptions->render(function (NotFoundHttpException $e) {
            return response()->json([
                'status' => 404,
                'success' => false,
                'message' => 'Route missing a parameter or this route could not be found',
                'data' => null,
            ], 404);
        });

        // Handle method not allowed
        $exceptions->render(function (MethodNotAllowedHttpException $e) {
            return response()->json([
                'status' => 405,
                'success' => false,
                'message' => 'The method for this request is not allowed.',
                'data' => null,
            ], 405);
        });

        // Handle bad method calls
        $exceptions->render(function (BadMethodCallException $e) {
            return response()->json([
                'status' => 500,
                'success' => false,
                'message' => 'The function you are trying to call seems to not exist.',
                'data' => null,
            ], 500);
        });

        // Configure which inputs should not be flashed on validation exceptions
        $exceptions->dontFlash([
            'current_password',
            'password',
            'password_confirmation',
        ]);
    })->create();
