<?php

namespace App\Http\Middleware;

use App\Services\UserStatusService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UpdateUserActivity
{
    protected UserStatusService $userStatusService;

    public function __construct(UserStatusService $userStatusService)
    {
        $this->userStatusService = $userStatusService;
    }

    /**
     * Handle an incoming request and update user's last seen timestamp
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Update last seen timestamp for authenticated users with real-time broadcasting
        if (auth()->check()) {
            $this->userStatusService->updateUserStatus(auth()->user());
        }

        return $next($request);
    }
}