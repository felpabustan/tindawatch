<?php

namespace App\Http\Middleware;

use App\Models\Store;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStoreSelected
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $storeId = $request->session()->get('current_store_id');

        if ($storeId) {
            $store = Store::query()->find((int) $storeId);

            if ($store instanceof Store && $user->belongsToStore($store)) {
                $request->attributes->set('currentStore', $store);

                return $next($request);
            }

            $request->session()->forget('current_store_id');
        }

        /** @var Store|null $store */
        $store = $user->stores()->orderBy('stores.id')->first();

        if ($store) {
            $request->session()->put('current_store_id', $store->id);
            $request->attributes->set('currentStore', $store);
        }

        return $next($request);
    }
}
