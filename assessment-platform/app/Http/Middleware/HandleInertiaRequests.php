<?php

namespace App\Http\Middleware;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        // Only the dashboard `web` guard exposes permission data; candidate
        // sessions render against the public Candidate/* pages and don't need it.
        $webUser = $request->user('web');

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $webUser instanceof User ? [
                    'id' => $webUser->id,
                    'name' => $webUser->name,
                    'email' => $webUser->email,
                    'is_super_admin' => $webUser->is_super_admin,
                    'permissions' => $webUser->getAllPermissions()->pluck('name')->toArray(),
                    'roles' => $webUser->getRoleNames()->toArray(),
                ] : null,
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'warning' => $request->session()->get('warning'),
                'info' => $request->session()->get('info'),
                'message' => $request->session()->get('message'),
            ],
        ];
    }
}
