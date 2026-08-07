<?php

namespace App\Http\Middleware;

use App\Models\Store;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        /** @var Store|null $currentStore */
        $currentStore = $request->attributes->get('currentStore');

        $stores = [];
        $role = null;

        if ($user) {
            $stores = $user->stores()
                ->orderBy('name')
                ->get(['stores.id', 'stores.name'])
                ->map(fn (Store $store) => [
                    'id' => $store->id,
                    'name' => $store->name,
                    'role' => $store->pivot->role,
                ])
                ->values()
                ->all();

            if ($currentStore) {
                $role = $user->roleIn($currentStore)?->value;
            }
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
            ],
            'currentStore' => $currentStore ? [
                'id' => $currentStore->id,
                'name' => $currentStore->name,
                'address' => $currentStore->address,
                'role' => $role,
            ] : null,
            'stores' => $stores,
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
