<?php
namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\CreateRoleRequest;
use App\Http\Requests\Backoffice\UpdateRoleRequest;
use App\Http\Routes\Backoffice\RoleRoutes;
use Digbang\Backoffice\Exceptions\ValidationException;
use Digbang\Backoffice\Http\BackofficeTrait;
use Digbang\Backoffice\Listings\Listing;
use Digbang\FontAwesome\Facade as FontAwesome;
use Digbang\Security\Exceptions\SecurityException;
use Digbang\Security\Permissions\Permissible;
use Digbang\Security\Permissions\Permission;
use Digbang\Security\Roles\Role;
use Digbang\Security\Users\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    use BackofficeTrait;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $titlePlural;

    /**
     * @var array
     */
    private $sortings = [
        'name' => 'r.name',
    ];

    /**
     * RoleController constructor.
     */
    public function __construct()
    {
        $this->title = trans('backoffice::auth.role');
        $this->titlePlural = trans('backoffice::auth.roles');
    }

    /**
     * @param Request $request
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index(Request $request)
    {
        $list = $this->getListing();

        $this->buildFilters($list);

        $this->buildListActions($list, $request);

        $list->fill($this->getData($request));

        $breadcrumb = $this->backoffice()->breadcrumb([
            trans('backoffice::default.home') => 'backoffice.index',
            $this->titlePlural,
        ]);

        return $this->view()->make('backoffice::index', [
            'title'      => $this->titlePlural,
            'list'       => $list,
            'breadcrumb' => $breadcrumb,
        ]);
    }

    public function create()
    {
        $label = trans('backoffice::default.new', ['model' => $this->title]);

        $form = $this->buildForm(
            $this->security()->url()->route(RoleRoutes::STORE),
            $label,
            'POST',
            $this->security()->url()->route(RoleRoutes::INDEX)
        );

        $breadcrumb = $this->backoffice()->breadcrumb([
            trans('backoffice::default.home') => 'backoffice.index',
            $this->titlePlural                => RoleRoutes::INDEX,
            $label,
        ]);

        return $this->view()->make('backoffice::create', [
            'title'      => $this->titlePlural,
            'form'       => $form,
            'breadcrumb' => $breadcrumb,
        ]);
    }

    public function store(CreateRoleRequest $request)
    {
        try {
            $roles = $this->security()->roles();

            /** @var Role|Permissible $role */
            $role = $roles->create($request->input('name'), $request->input('slug') ?: null);

            if ($request->input('permissions') && $role instanceof Permissible) {
                foreach ((array) $request->input('permissions') as $permission) {
                    $role->addPermission($permission);
                }

                $roles->save($role);
            }

            return $this->redirect()->to(
                $this->security()->url()->route(RoleRoutes::SHOW, $role->getRoleSlug())
            );
        } catch (ValidationException $e) {
            return $this->redirect()->back()->withInput()->withErrors($e->getErrors());
        }
    }

    public function show(Role $role)
    {
        $breadcrumb = $this->backoffice()->breadcrumb([
            trans('backoffice::default.home') => 'backoffice.index',
            $this->titlePlural                => RoleRoutes::INDEX,
            $role->getName(),
        ]);

        $data = [
            trans('backoffice::auth.name')        => $role->getName(),
            trans('backoffice::auth.permissions') => $this->permissionParser()->toViewTable(
                $this->security()->permissions()->all(),
                $role
            ),
        ];

        $actions = $this->backoffice()->actions();

        try {
            $actions->link(
                $this->security()->url()->route(RoleRoutes::EDIT, $role->getRoleSlug()),
                FontAwesome::icon('edit') . ' ' . trans('backoffice::default.edit'),
                ['class' => 'btn btn-success']
            );
        } catch (SecurityException $e) {
        }

        try {
            $actions->link(
                $this->security()->url()->route(RoleRoutes::INDEX),
                trans('backoffice::default.back'),
                ['class' => 'btn btn-default']
            );
        } catch (SecurityException $e) {
        }

        $topActions = $this->backoffice()->actions();

        try {
            $topActions->link(
                $this->security()->url()->route(RoleRoutes::INDEX),
                FontAwesome::icon('arrow-left') . ' ' . trans('backoffice::default.back')
            );
        } catch (SecurityException $e) {
        }

        return $this->view()->make('backoffice::show', [
            'title'      => $this->titlePlural,
            'breadcrumb' => $breadcrumb,
            'label'      => $role->getName(),
            'data'       => $data,
            'actions'    => $actions,
            'topActions' => $topActions,
        ]);
    }

    public function edit(Role $role)
    {
        $form = $this->buildForm(
            $this->security()->url()->route(RoleRoutes::UPDATE, $role->getRoleSlug()),
            trans('backoffice::default.edit') . ' ' . $role->getName(),
            'PUT',
            $this->security()->url()->route(RoleRoutes::SHOW, $role->getRoleSlug())
        );

        $permissions = $role->getPermissions()->map(function (Permission $permission) {
            return $permission->getName();
        })->toArray();

        $form->fill([
            'name'          => $role->getName(),
            'permissions[]' => $permissions,
        ]);

        $breadcrumb = $this->backoffice()->breadcrumb([
            trans('backoffice::default.home') => 'backoffice.index',
            $this->titlePlural                => RoleRoutes::INDEX,
            $role->getName()                  => [RoleRoutes::SHOW, $role->getRoleSlug()],
            trans('backoffice::default.edit'),
        ]);

        return $this->view()->make('backoffice::edit', [
            'title'      => $this->titlePlural,
            'form'       => $form,
            'breadcrumb' => $breadcrumb,
        ]);
    }

    public function update(Role $role, UpdateRoleRequest $request)
    {
        try {
            $role->setName($request->input('name'));

            if ($role instanceof Permissible) {
                $role->syncPermissions((array) $request->input('permissions'));
            }

            $this->security()->roles()->save($role);

            return $this->redirect()->to(
                $this->security()->url()->route(RoleRoutes::SHOW, [$role->getRoleSlug()])
            );
        } catch (ValidationException $e) {
            return $this->redirect()->back()->withInput()->withErrors($e->getErrors());
        }
    }

    public function destroy(Role $role)
    {
        try {
            $this->security()->roles()->delete($role);

            return $this->redirect()->to(
                $this->security()->url()->route(RoleRoutes::INDEX)
            )->withSuccess(
                trans(
                    'backoffice::default.delete_msg',
                    ['model' => $this->title, 'id' => $role->getName()]
                )
            );
        } catch (ValidationException $e) {
            return $this->redirect()->back()->withDanger(implode('<br/>', $e->getErrors()));
        }
    }

    public function export(Request $request)
    {
        $list = $this->getListing();

        $list->fill($this->getData($request, null));

        $columns = $list->columns()->hide(['id'])->sortable([]);
        $rows = $list->rows();

        $fileName = (new \DateTime())->format('Y-m-d') . '_' . $this->titlePlural;

        $this->excel()->create(Str::slug($fileName), function ($excel) use ($columns, $rows) {
            $excel->sheet($this->titlePlural, function ($sheet) use ($columns, $rows) {
                $sheet->loadView('backoffice::lists.export', [
                    'bulkActions' => [],
                    'rowActions'  => [],
                    'columns'     => $columns->visible(),
                    'items'       => $rows,
                ]);
            });
        })->download('xls');
    }

    protected function buildForm($target, $label, $method = 'POST', $cancelAction = '', $options = [])
    {
        $form = $this->backoffice()->form($target, $label, $method, $cancelAction, $options);

        $inputs = $form->inputs();

        $inputs->text('name', trans('backoffice::auth.name'));
        $inputs->dropdown(
            'permissions',
            trans('backoffice::auth.permissions'),
            $this->permissionParser()->toDropdownArray($this->security()->permissions()->all()),
            ['multiple' => 'multiple', 'class' => 'multiselect']
        );

        return $form;
    }

    /**
     * @param Listing $list
     */
    protected function buildFilters(Listing $list)
    {
        $filters = $list->filters();

        // Here we add filters to the list
        $filters->string('name', trans('backoffice::auth.name'), ['class' => 'form-control']);
        $filters->dropdown(
            'permission',
            trans('backoffice::auth.permissions'),
            $this->permissionParser()->toDropdownArray($this->security()->permissions()->all(), true),
            ['class' => 'form-control']
        );
    }

    /**
     * @return Listing
     */
    protected function getListing()
    {
        $listing = $this->backoffice()->listing([
            'name'  => trans('backoffice::auth.name'),
            'users' => trans('backoffice::auth.users'),
            'id', 'slug',
        ]);

        $columns = $listing->columns();
        $columns->hide(['id', 'slug'])->sortable(['name']);

        $listing->addValueExtractor('id', function (Role $role) {
            return $role->getRoleId();
        });

        $listing->addValueExtractor('slug', function (Role $role) {
            return $role->getRoleSlug();
        });

        $listing->addValueExtractor('users', function (Role $role) {
            $users = [];
            foreach ($role->getUsers() as $user) {
                /* @type User $user */
                $users[] = $user->getUsername();
            }

            if (count($users) < 5) {
                return implode(', ', $users);
            }

            return implode(', ', array_slice($users, 0, 4)) . '... (+' . (count($users) - 4) . ')';
        });

        return $listing;
    }

    protected function buildListActions(Listing $list, Request $request)
    {
        $actions = $this->backoffice()->actions();

        try {
            $actions->link(
                $this->security()->url()->route(RoleRoutes::CREATE),
                FontAwesome::icon('plus') . ' ' . trans('backoffice::default.new', ['model' => $this->title]),
                ['class' => 'btn btn-primary']
            );
        } catch (SecurityException $e) {
        }

        try {
            $actions->link(
                $this->security()->url()->route(RoleRoutes::EXPORT, $request->all()),
                FontAwesome::icon('file-excel-o') . ' ' . trans('backoffice::default.export'),
                ['class' => 'btn btn-success']
            );
        } catch (SecurityException $e) {
        }

        $list->setActions($actions);

        $rowActions = $this->backoffice()->actions();

                // View icon
        $rowActions->link(function (Collection $row) {
            try {
                return $this->security()->url()->route(RoleRoutes::SHOW, $row['slug']);
            } catch (SecurityException $e) {
                return false;
            }
        }, FontAwesome::icon('eye'), [
            'data-toggle'    => 'tooltip',
            'data-placement' => 'top',
            'title'          => trans('backoffice::default.show'),
        ]);

        // Edit icon
        $rowActions->link(function (Collection $row) {
            try {
                return $this->security()->url()->route(RoleRoutes::EDIT, $row['slug']);
            } catch (SecurityException $e) {
                return false;
            }
        }, FontAwesome::icon('edit'), [
            'data-toggle'    => 'tooltip',
            'data-placement' => 'top',
            'title'          => trans('backoffice::default.edit'),
        ]);
        // Delete icon
        $rowActions->form(
            function (Collection $row) {
                try {
                    return $this->security()->url()->route(RoleRoutes::DESTROY, $row['slug']);
                } catch (SecurityException $e) {
                    return false;
                }
            },
            FontAwesome::icon('times'),
            'DELETE',
            [
                'class'          => 'text-danger',
                'data-toggle'    => 'tooltip',
                'data-placement' => 'top',
                'data-confirm'   => trans('backoffice::default.delete-confirm'),
                'title'          => trans('backoffice::default.delete'),
            ]
        );

        $list->setRowActions($rowActions);
    }

    /**
     * @param Request $request
     * @param int     $limit
     *
     * @throws \Doctrine\ORM\Mapping\MappingException
     *
     * @return array
     */
    protected function getData(Request $request, $limit = 10)
    {
        /** @var \Digbang\Backoffice\Repositories\DoctrineRoleRepository $roles */
        $roles = $this->security()->roles();

        return $roles->search(
            $request->only(['name', 'permission']),
            $this->getSorting($request),
            $limit,
            ($request->input('page', 1) - 1) * $limit
        );
    }

    private function getSorting(Request $request)
    {
        $sortBy = $request->input('sort_by')    ?: 'name';
        $sortSense = $request->input('sort_sense') ?: 'asc';

        return [
            $this->sortings[$sortBy] => $sortSense,
        ];
    }
}
