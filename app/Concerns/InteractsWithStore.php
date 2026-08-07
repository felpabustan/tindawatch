<?php

namespace App\Concerns;

use App\Models\Store;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

trait InteractsWithStore
{
    protected function currentStore(Request $request): Store
    {
        /** @var Store|null $store */
        $store = $request->attributes->get('currentStore');

        if (! $store) {
            throw new HttpException(403, 'No store selected.');
        }

        return $store;
    }

    protected function ensureCatalogAccess(Request $request): void
    {
        $store = $this->currentStore($request);

        if (! $request->user()?->canManageCatalog($store)) {
            abort(403, 'You cannot manage the product catalog for this store.');
        }
    }

    protected function ensureTeamAccess(Request $request): void
    {
        $store = $this->currentStore($request);

        if (! $request->user()?->canManageTeam($store)) {
            abort(403, 'You cannot manage the team for this store.');
        }
    }

    protected function ensureReportsAccess(Request $request): void
    {
        $store = $this->currentStore($request);

        if (! $request->user()?->canManageCatalog($store)) {
            abort(403, 'You cannot view reports for this store.');
        }
    }
}
