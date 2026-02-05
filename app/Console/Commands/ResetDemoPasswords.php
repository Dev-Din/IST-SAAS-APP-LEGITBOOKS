<?php

namespace App\Console\Commands;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Console\Command;

class ResetDemoPasswords extends Command
{
    protected $signature = 'auth:reset-demo-passwords
                            {--password=password : The plain password to set}';

    protected $description = 'Reset demo admin and tenant user passwords so they can log in (Admin: admin@legitbooks.com, Demo user: user@demo.com, admin@demo.com)';

    public function handle(): int
    {
        $plainPassword = $this->option('password');

        $admin = Admin::where('email', 'admin@legitbooks.com')->first();
        if ($admin) {
            $admin->password = $plainPassword;
            $admin->is_active = true;
            $admin->save();
            $this->info('Admin (admin@legitbooks.com) password reset. Use password: ' . $plainPassword);
        } else {
            $this->warn('Admin with email admin@legitbooks.com not found.');
        }

        $users = User::whereIn('email', ['user@demo.com', 'admin@demo.com'])->get();
        foreach ($users as $user) {
            $user->password = $plainPassword;
            $user->is_active = true;
            $user->save();
            $this->info('User (' . $user->email . ') password reset. Use password: ' . $plainPassword);
        }

        if ($users->isEmpty()) {
            $this->warn('No demo users (user@demo.com or admin@demo.com) found.');
        }

        $this->info('Done. You can now log in with the credentials above.');

        return self::SUCCESS;
    }
}
