<?php

namespace App\Providers;

use Faker\Generator as Faker;
use Illuminate\Support\ServiceProvider;
use LaravelDoctrine\ORM\Testing\Factory as EntityFactory;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Implementation bindings.
     *
     * @var string[]
     */
    private $bindings = [
        //
    ];

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        foreach ($this->bindings as $abstract => $concrete) {
            $this->app->bind($abstract, $concrete);
        }

        $this->app->afterResolving(EntityFactory::class, function (EntityFactory $factory) {
            $this->defineFakeEntities($factory);
        });
    }

    /**
     * Here you may define all of your entity factories. Entity factories give
     * you a convenient way to create entities for testing and seeding your
     * database. Just tell the factory how a default entity should look.
     *
     * @param EntityFactory $factory
     */
    private function defineFakeEntities(EntityFactory $factory)
    {
        $factory->define(\App\User::class, function (Faker $faker) {
            static $password;

            return [
                'name'           => $faker->name,
                'email'          => $faker->unique()->safeEmail,
                'password'       => $password ?: $password = bcrypt('secret'),
                'remember_token' => str_random(10),
            ];
        });
    }
}
