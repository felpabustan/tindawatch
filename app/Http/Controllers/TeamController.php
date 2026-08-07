<?php

namespace App\Http\Controllers;

use App\Concerns\InteractsWithStore;
use App\Contracts\Repositories\TeamRepositoryInterface;
use App\Enums\StoreRole;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    use InteractsWithStore;

    public function __construct(
        private TeamRepositoryInterface $team,
    ) {}

    public function index(Request $request): Response
    {
        $this->ensureTeamAccess($request);
        $store = $this->currentStore($request);

        $members = $this->team->membersForStore($store)->map(fn (User $user) => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->pivot->role,
        ]);

        return Inertia::render('Team/Index', [
            'members' => $members,
            'roles' => collect(StoreRole::cases())->map(fn (StoreRole $role) => [
                'value' => $role->value,
                'label' => ucfirst($role->value),
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureTeamAccess($request);
        $store = $this->currentStore($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::defaults()],
            'role' => ['required', Rule::enum(StoreRole::class)],
        ]);

        $role = StoreRole::from($validated['role']);

        if ($role === StoreRole::Owner && $request->user()->roleIn($store) !== StoreRole::Owner) {
            abort(403, 'Only owners can add another owner.');
        }

        $user = $this->team->createUser([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
        ]);

        $this->team->attachMember($store, $user, $role);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Team member added.']);

        return back();
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $this->ensureTeamAccess($request);
        $store = $this->currentStore($request);

        if (! $user->belongsToStore($store)) {
            abort(404);
        }

        $validated = $request->validate([
            'role' => ['required', Rule::enum(StoreRole::class)],
        ]);

        $role = StoreRole::from($validated['role']);

        if ($user->id === $store->owner_id && $role !== StoreRole::Owner) {
            return back()->withErrors(['role' => 'Cannot demote the store owner.']);
        }

        $this->team->updateMemberRole($store, $user, $role);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Role updated.']);

        return back();
    }
}
