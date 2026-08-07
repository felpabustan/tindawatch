<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\StoreRole;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'store_name' => ['nullable', 'string', 'max:255'],
        ])->validate();

        return DB::transaction(function () use ($input) {
            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => $input['password'],
            ]);

            $storeName = trim((string) ($input['store_name'] ?? ''));
            if ($storeName === '') {
                $storeName = $user->name."'s Tindahan";
            }

            $store = Store::query()->create([
                'owner_id' => $user->id,
                'name' => $storeName,
            ]);

            $store->users()->attach($user->id, ['role' => StoreRole::Owner->value]);

            return $user;
        });
    }
}
