<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateOperator extends Command
{
    protected $signature = 'operator:create {email} {--name=Operator} {--password= : Leave empty to be asked securely}';

    protected $description = 'Create (or reset the password of) an operator login for the console.';

    public function handle(): int
    {
        $password = $this->option('password') ?: $this->secret('Password (min 12 characters)');

        if (strlen((string) $password) < 12) {
            $this->error('Password must be at least 12 characters.');

            return self::FAILURE;
        }

        User::updateOrCreate(
            ['email' => $this->argument('email')],
            ['name' => $this->option('name'), 'password' => Hash::make($password)]
        );

        $this->info('Operator ready: '.$this->argument('email'));

        return self::SUCCESS;
    }
}
