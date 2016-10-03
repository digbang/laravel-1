<?php
namespace App\Console\Commands\Backoffice;

use Digbang\Security\Contracts\SecurityApi;
use Digbang\Security\Permissions\Permissible;

class RolePermissionAddCommand extends AddPermissionCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backoffice:roles:permissions:add {role : The role slug} {permissions? : A comma-separated list of permissions. If omitted, all permissions will be added.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add permission/s to a backoffice role.';

    /**
     * {@inheritdoc}
     */
    protected function getPermissible(SecurityApi $security)
    {
        $slug = $this->argument('role');
        $role = $security->roles()->findOneBy(['slug' => $slug]);

        $this->assertRole($role, "Role [$slug] does not exist.");

        return $role;
    }

    /**
     * @param object|null $role
     * @param string      $message
     *
     * @return void
     */
    private function assertRole($role, $message)
    {
        if (!$role) {
            $this->error($message);

            exit(1);
        }

        if (!$role instanceof Permissible) {
            $this->error('The configured Role class needs to extend ' . Permissible::class . ' to use permissions.');

            exit(2);
        }
    }
}
