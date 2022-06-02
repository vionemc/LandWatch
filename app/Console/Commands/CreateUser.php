<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Validation\Factory;

use function array_diff_key;
use function array_flip;

class CreateUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create {name} {email} {password}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create user account';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @param Factory $validatorFactory
     * @param Hasher $hasher
     * @return int
     */
    public function handle(Factory $validatorFactory, Hasher $hasher): int
    {
        $arguments = array_diff_key($this->arguments(), array_flip(['command']));
        $validator = $validatorFactory->make($arguments, [
           'name' => 'required|string|max:255',
           'email' => 'required|string|email|max:255|unique:users',
           'password' => 'required|string|min:8',
       ]);

        if ($validator->fails()) {
            $this->info('User not created. See error messages below:');

            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return 1;
        }

        $user = new User();
        $user->name = $this->argument('name');
        $user->email = $this->argument('email');
        $user->password = $hasher->make($this->argument('password'));
        $user->save();

        $this->info('User account with id: ' . $user->id . ' created.');

        return 0;
    }
}
