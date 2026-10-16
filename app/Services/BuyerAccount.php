<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Finds the student account for a buyer e-mail, creating it on the first purchase.
 * New accounts get a random password: the access e-mail carries a link to set one.
 */
class BuyerAccount
{
    public function resolve(string $email, string $name, ?string $phone = null): User
    {
        $email = Str::lower(trim($email));

        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => trim($name) ?: Str::before($email, '@'),
                'phone' => $phone,
                'password' => Str::password(32),
                'role' => UserRole::Student,
            ],
        );

        if (! $user->wasRecentlyCreated && blank($user->phone) && filled($phone)) {
            $user->update(['phone' => $phone]);
        }

        return $user;
    }
}
