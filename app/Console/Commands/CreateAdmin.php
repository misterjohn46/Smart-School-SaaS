<?php

namespace App\Console\Commands;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class CreateAdmin extends Command
{
    protected $signature = 'app:create-admin {email? : Email address of the administrator}';

    protected $description = 'Create a new administrator or promote an existing user';

    public function handle(): int
    {
        $email = strtolower((string) ($this->argument('email') ?: $this->ask('Email')));
        $existingUser = User::where('email', $email)->first();

        if ($existingUser) {
            $existingUser->assignRole(Role::findOrCreate(RoleName::Admin->value));
            $this->components->info("{$email} is now an administrator.");

            return self::SUCCESS;
        }

        $name = (string) $this->ask('Name');
        $password = (string) $this->secret('Password');
        $confirmation = (string) $this->secret('Confirm password');
        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $confirmation,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        $user = User::create($validator->validated());
        $user->forceFill(['email_verified_at' => now()])->save();
        $user->assignRole(Role::findOrCreate(RoleName::Admin->value));
        $this->components->info("Administrator {$email} created.");

        return self::SUCCESS;
    }
}
