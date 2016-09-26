<?php

namespace App\Console\Commands\Backoffice;

use Digbang\Security\Roles\Role;
use Digbang\Security\Roles\Roleable;
use Digbang\Security\SecurityContext;
use Digbang\Security\Users\User;
use Doctrine\ORM\EntityManager;
use Illuminate\Console\Command;

class UserRoleAddCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backoffice:users:roles:add {username : The user\'s username} {role : The role slug}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add a role to a backoffice user.';

    /**
     * Execute the console command.
     *
     * @param SecurityContext $securityContext
     * @param EntityManager   $entityManager
     */
    public function handle(SecurityContext $securityContext, EntityManager $entityManager)
    {
        $security = $securityContext->getSecurity('backoffice');

        $username = $this->argument('username');
        $roleName = $this->argument('role');

        /** @type User|Roleable $user */
        $user = $security->users()->findOneBy(['username' => $username]);
    
        $this->assertUser($user, "Username [$username] does not exist.");
    
        /** @type Role $role */
        $role = $security->roles()->findBySlug($roleName);
    
        $this->assertRoleExists($role, "Role [$roleName] does not exist. You must use the role slug to identify it.");
    
        $user->addRole($role);

        $entityManager->persist($user);
        $entityManager->flush();

        $this->info("Role [$roleName] added to user [$username].");
    }
    
    /**
     * @param User|null $user
     * @param string $message
     *
     * @return void
     */
    protected function assertUser($user, $message = "User does not exist.")
    {
        if (!$user) {
            $this->error($message);
            
            exit(1);
        }
    
        if (!$user instanceof Roleable) {
            $this->error(
                "The configured User class needs to extend " . Roleable::class .
                " to use roles."
            );
        
            exit(2);
        }
    }
    
    /**
     * @param Role|null $role
     * @param string $message
     *
     * @return void
     */
    protected function assertRoleExists($role, $message)
    {
        if (!$role) {
            $this->error($message);
            
            exit(3);
        }
    }
}
