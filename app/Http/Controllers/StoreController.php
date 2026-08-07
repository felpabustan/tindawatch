<?php

namespace App\Http\Controllers;

use App\Concerns\InteractsWithStore;
use App\Contracts\Repositories\StoreRepositoryInterface;
use App\Enums\StoreRole;
use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class StoreController extends Controller
{
    use InteractsWithStore;

    public function __construct(
        private StoreRepositoryInterface $stores,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $maxStores = (int) config('tindawatch.max_stores_per_user', 3);
        $ownedCount = $this->stores->ownedCount($user);

        $stores = $this->stores->forUser($user)->map(fn (Store $store) => [
            'id' => $store->id,
            'name' => $store->name,
            'address' => $store->address,
            'role' => $store->pivot->role,
            'is_current' => (int) $request->session()->get('current_store_id') === $store->id,
            'is_owner' => (int) $store->owner_id === (int) $user->id,
        ]);

        return Inertia::render('Stores/Index', [
            'stores' => $stores,
            'maxStores' => $maxStores,
            'ownedCount' => $ownedCount,
            'canCreateStore' => $ownedCount < $maxStores,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $maxStores = (int) config('tindawatch.max_stores_per_user', 3);
        $ownedCount = $this->stores->ownedCount($request->user());

        if ($ownedCount >= $maxStores) {
            return back()->withErrors([
                'name' => "You can manage up to {$maxStores} stores on your current plan.",
            ]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($request, $validated) {
            $store = $this->stores->create([
                'owner_id' => $request->user()->id,
                'name' => $validated['name'],
                'address' => $validated['address'] ?? null,
            ]);

            $this->stores->attachUser($store, $request->user(), StoreRole::Owner);
            $request->session()->put('current_store_id', $store->id);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Store created.']);

        return to_route('stores.index');
    }

    public function update(Request $request, Store $store): RedirectResponse
    {
        if ((int) $store->owner_id !== (int) $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        $this->stores->update($store, $validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Store updated.']);

        return back();
    }

    public function switch(Request $request, Store $store): RedirectResponse
    {
        if (! $request->user()->belongsToStore($store)) {
            abort(403);
        }

        $request->session()->put('current_store_id', $store->id);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Switched to {$store->name}."]);

        return back();
    }
}
