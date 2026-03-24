<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Auth\AuthService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

final class CreateUserCommand extends Command
{
    protected $signature = 'user:create';

    protected $description = 'Create a user (interactive) and print their data plus a Sanctum API token';

    public function __construct(
        private readonly AuthService $authService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Create a new user (input is hidden for passwords).');
        $this->newLine();

        $name = trim((string) $this->ask('Name'));
        $email = trim((string) $this->ask('Email'));

        $password = $this->secret('Password (leave empty to generate a random one)');
        $passwordWasGenerated = false;

        if ($password === null || $password === '') {
            $password = Str::password(24);
            $passwordWasGenerated = true;
            $this->comment('A random password was generated; it will be shown below.');
        } else {
            $confirm = $this->secret('Confirm password');
            if ($password !== $confirm) {
                $this->error('Passwords do not match.');

                return self::FAILURE;
            }
        }

        $validator = $this->validator($name, $email, $password);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $user = $this->authService->register($name, $email, $password);
        $token = $this->authService->createToken($user);

        $rows = [
            ['id', (string) $user->id],
            ['name', $user->name],
            ['email', $user->email],
            ['is_active', $user->is_active ? 'true' : 'false'],
            ['created_at', $user->created_at?->toIso8601String() ?? ''],
        ];

        if ($passwordWasGenerated) {
            $rows[] = ['password (generated)', $password];
        }

        $this->newLine();
        $this->table(['Field', 'Value'], $rows);

        $this->newLine();
        $this->line('<fg=green>Token (Bearer)</>');
        $this->line($token);

        return self::SUCCESS;
    }

    private function validator(string $name, string $email, string $password): Validator
    {
        return validator(
            [
                'name' => $name,
                'email' => $email,
                'password' => $password,
            ],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', Password::defaults()],
            ]
        );
    }
}
