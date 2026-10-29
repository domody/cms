<?php

namespace App\Console\Commands;

use App\Models\AccessLevel;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class CreateAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cms:create-admin
        {--name= : The admin\'s name}
        {--email= : The admin\'s email address}
        {--password= : The password (generated and printed once if omitted in non-interactive mode)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an admin user (the way to make the first one on a new install)';

    public function handle(): int
    {
        $name = $this->option('name') ?: text('Name', required: true);
        $email = $this->option('email') ?: text('Email', required: true);
        $password = $this->option('password')
            ?: ($this->input->isInteractive() ? password('Password (min 8 characters)', required: true) : null);

        $generated = $password === null;
        $password ??= Str::password(16, symbols: false);

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:8', 'max:255'],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        $user = new User(['name' => $name, 'email' => $email, 'password' => $password]);
        $user->access_level = AccessLevel::ADMIN;
        $user->save();

        $this->components->info("Admin [{$user->email}] created.");

        if ($generated) {
            $this->line("Generated password: {$password}");
        }

        return self::SUCCESS;
    }
}
