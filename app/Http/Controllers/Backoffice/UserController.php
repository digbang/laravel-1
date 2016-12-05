<?php
namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\CreateUserRequest;
use App\Http\Requests\Backoffice\UpdateUserRequest;
use App\Http\Routes\Backoffice\AuthRoutes;
use App\Http\Routes\Backoffice\DashboardRoutes;
use App\Http\Routes\Backoffice\UserRoutes;
use Carbon\Carbon;
use Digbang\Backoffice\Exceptions\ValidationException;
use Digbang\Backoffice\Http\BackofficeTrait;
use Digbang\Backoffice\Listings\Listing;
use Digbang\Backoffice\Repositories\DoctrineUserRepository;
use Digbang\FontAwesome\Facade as FontAwesome;
use Digbang\Security\Activations\Activation;
use Digbang\Security\Exceptions\SecurityException;
use Digbang\Security\Permissions\Permissible;
use Digbang\Security\Permissions\Permission;
use Digbang\Security\Roles\Role;
use Digbang\Security\Roles\Roleable;
use Digbang\Security\Users\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class UserController extends Controller
{
    use BackofficeTrait;
    use SendsEmails;

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
        'firstName' => 'u.name.firstName',
        'lastName'  => 'u.name.lastName',
        'lastLogin' => 'u.lastLogin',
        'email'     => 'u.email.address',
        'username'  => 'u.username',
    ];

    public function __construct()
    {
        $this->title = trans('backoffice::auth.user');
        $this->titlePlural = trans('backoffice::auth.users');
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
            trans('backoffice::default.home') => DashboardRoutes::HOME,
            $this->titlePlural,
        ]);

        return $this->view()->make('backoffice::index', [
            'user'       => $this->security()->getUser(),
            'title'      => $this->titlePlural,
            'list'       => $list,
            'breadcrumb' => $breadcrumb,
        ]);
    }

    public function create()
    {
        $label = trans('backoffice::default.new', ['model' => $this->title]);

        $form = $this->buildForm(
            $this->url()->route(UserRoutes::STORE),
            $label,
            'POST',
            $this->url()->route(UserRoutes::INDEX)
        );

        $breadcrumb = $this->backoffice()->breadcrumb([
            trans('backoffice::default.home') => DashboardRoutes::HOME,
            $this->titlePlural                => UserRoutes::INDEX,
            $label,
        ]);

        return $this->view()->make('backoffice::create', [
            'title'      => $this->titlePlural,
            'form'       => $form,
            'breadcrumb' => $breadcrumb,
        ]);
    }

    public function store(CreateUserRequest $request)
    {
        $input = $request->only([
            'firstName',
            'lastName',
            'email',
            'password',
            'activated',
            'username',
            'roles',
            'permissions',
        ]);

        try {
            /** @var User $user */
            $user = $this->security()->users()->create($input, function (User $user) use ($input) {
                $this->addRoles($user, $input['roles']);
            });

            if ($input['activated']) {
                $this->security()->activate($user);
            } else {
                /** @var Activation $activation */
                $activation = $this->security()->activations()->create($user);

                $this->sendActivation($user,route(AuthRoutes::ACTIVATE, [$activation->getCode()]));
            }

            return $this->redirect()->to($this->url()->route(UserRoutes::SHOW, $user->getUsername()));
        } catch (SecurityException $e) {
            return $this->redirect()->to($this->url()->route(DashboardRoutes::HOME));
        }
    }

    public function show(User $user)
    {
        $breadcrumb = $this->backoffice()->breadcrumb([
            trans('backoffice::default.home') => DashboardRoutes::HOME,
            $this->titlePlural                => UserRoutes::INDEX,
            trans('backoffice::auth.user_name', [
                'name'     => $user->getName()->getFirstName(),
                'lastname' => $user->getName()->getLastName(),
            ]),
        ]);

        $data = [
            trans('backoffice::auth.first_name')   => $user->getName()->getFirstName(),
            trans('backoffice::auth.last_name')    => $user->getName()->getLastName(),
            trans('backoffice::auth.email')        => $user->getEmail(),
            trans('backoffice::auth.username')     => $user->getUsername(),
            trans('backoffice::auth.permissions')  => $this->permissionParser()->toViewTable($this->security()->permissions()->all(), $user),
            trans('backoffice::auth.activated')    => trans('backoffice::default.' . ($user->isActivated() ? 'yes' : 'no')),
            trans('backoffice::auth.activated_at') => $user->isActivated() ? $user->getActivatedAt()->format(trans('backoffice::default.datetime_format')) : '-',
            trans('backoffice::auth.last_login')   => $user->getLastLogin() ? $user->getLastLogin()->format(trans('backoffice::default.datetime_format')) : '-',
        ];

        if ($user instanceof Roleable) {
            /** @var User|Roleable $user */
            $roles = $user->getRoles();

            /* @type \Doctrine\Common\Collections\Collection $roles */
            $data[trans('backoffice::auth.roles')] = implode(', ', $roles->map(function (Role $role) {
                return $role->getName();
            })->toArray());
        }

        // Actions with security concerns
        $actions = $this->backoffice()->actions();
        try {
            $actions->link($this->url()->route(UserRoutes::EDIT, $user->getUsername()), FontAwesome::icon('edit') . ' ' . trans('backoffice::default.edit'), ['class' => 'btn btn-success']);
        } catch (SecurityException $e) { /* Do nothing */
        }
        try {
            $actions->link($this->url()->route(UserRoutes::INDEX), trans('backoffice::default.back'), ['class' => 'btn btn-default']);
        } catch (SecurityException $e) { /* Do nothing */
        }

        $topActions = $this->backoffice()->actions();

        try {
            $topActions->link($this->url()->route(UserRoutes::INDEX), FontAwesome::icon('arrow-left') . ' ' . trans('backoffice::default.back'));
        } catch (SecurityException $e) { /* Do nothing */
        }

        return $this->view()->make('backoffice::show', [
            'title'      => $this->titlePlural,
            'breadcrumb' => $breadcrumb,
            'label'      => trans('backoffice::auth.user_name', [
                'name'     => $user->getName()->getFirstName(),
                'lastname' => $user->getName()->getLastName(),
            ]),
            'data'       => $data,
            'actions'    => $actions,
            'topActions' => $topActions,
        ]);
    }

    public function edit(User $user)
    {
        $form = $this->buildForm(
            $this->url()->route(UserRoutes::UPDATE, $user->getUsername()),
            trans('backoffice::default.edit') . ' ' . trans('backoffice::auth.user_name', ['name' => $user->getName()->getFirstName(), 'lastname' => $user->getName()->getLastName()]),
            'PUT',
            $this->url()->route(UserRoutes::SHOW, $user->getUsername()),
            [],
            $user
        );

        $data = [
            'firstName' => $user->getName()->getFirstName(),
            'lastName'  => $user->getName()->getLastName(),
            'email'     => $user->getEmail(),
            'username'  => $user->getUsername(),
        ];

        /** @var User|Roleable|Permissible $user */
        if ($user instanceof Roleable) {
            $roles = $user->getRoles();

            /* @type \Doctrine\Common\Collections\Collection $roles */
            $data['roles[]'] = $roles->map(function (Role $role) {
                return $role->getRoleSlug();
            })->toArray();
        }

        if ($user instanceof Permissible) {
            $data['permissions[]'] = [];
            foreach ($this->security()->permissions()->all() as $permission) {
                if ($user->hasAccess($permission)) {
                    $data['permissions[]'][] = (string) $permission;
                }
            }
        }

        $form->fill($data);

        $breadcrumb = $this->backoffice()->breadcrumb([
            trans('backoffice::default.home') => DashboardRoutes::HOME,
            $this->titlePlural                => UserRoutes::INDEX,
            trans('backoffice::auth.user_name', [
                'name'     => $user->getName()->getFirstName(),
                'lastname' => $user->getName()->getLastName(),
            ]) => [UserRoutes::SHOW, $user->getUsername()],
            trans('backoffice::default.edit'),
        ]);

        return $this->view()->make('backoffice::edit', [
            'title'      => $this->titlePlural,
            'form'       => $form,
            'breadcrumb' => $breadcrumb,
        ]);
    }

    public function update(User $user, UpdateUserRequest $request)
    {
        try {
            $this->security()->users()->update($user, $request->only([
                'firstName',
                'lastName',
                'email',
                'username',
                'password',
                'activated',
                'roles',
                'permissions',
            ]));

            return $this->redirect()->to($this->url()->route(UserRoutes::SHOW, [$user->getUsername()]));
        } catch (SecurityException $e) {
            return $this->redirect()->to($this->url()->route(DashboardRoutes::HOME));
        }
    }

    public function destroy(User $user)
    {
        try {
            $this->security()->users()->destroy($user);

            // Redirect to the listing
            return $this->redirect()->to($this->url()->route(UserRoutes::INDEX))->withSuccess(
                trans('backoffice::default.delete_msg', ['model' => $this->title, 'id' => $user->getEmail()])
            );
        } catch (ValidationException $e) {
            return $this->redirect()->back()->withDanger(implode('<br/>', $e->getErrors()));
        } catch (SecurityException $e) {
            return $this->redirect()->to($this->url()->route(DashboardRoutes::HOME))->withDanger(
                trans('backoffice::auth.permission_error')
            );
        }
    }

    public function export(Request $request)
    {
        $list = $this->getListing();

        $list->fill($this->getData($request, null));

        $columns = $list->columns()->hide(['id', 'firstName', 'lastName'])->sortable([]);
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

    public function resetPassword(User $user)
    {
        /** @var \Digbang\Security\Reminders\Reminder $reminder */
        $reminder = $this->security()->reminders()->create($user);

        $this->sendPasswordReset(
            $user,
            route(AuthRoutes::RESET, [$user->getUsername(), $reminder->getCode()])
        );

        return $this->redirect()->back()->withSuccess(trans('backoffice::auth.reset-password.email-sent', ['email' => $user->getEmail()]));
    }

    public function resendActivation(User $user)
    {
        $activation = $this->security()->activations()->create($user);

        $this->sendActivation(
            $user,
            route(AuthRoutes::ACTIVATE, [$activation->getCode()])
        );

        return $this->redirect()->back()->withSuccess(trans('backoffice::auth.activation.email-sent', ['email' => $user->getEmail()]));
    }

    protected function buildForm($target, $label, $method = 'POST', $cancelAction = '', $options = [], User $user = null)
    {
        $form = $this->backoffice()->form($target, $label, $method, $cancelAction, $options);

        $inputs = $form->inputs();

        $inputs->text('firstName',                 trans('backoffice::auth.first_name'));
        $inputs->text('lastName',                  trans('backoffice::auth.last_name'));
        $inputs->text('email',                     trans('backoffice::auth.email'));
        $inputs->text('username',                  trans('backoffice::auth.username'));
        $inputs->password('password',              trans('backoffice::auth.password'));
        $inputs->password('password_confirmation', trans('backoffice::auth.confirm_password'));

        if (!$user) {
            $inputs->checkbox('activated', trans('backoffice::auth.activated'));
        }

        $roles = $this->security()->roles()->findAll();

        $options = [];
        $rolePermissions = [];
        foreach ($roles as $role) {
            /* @type \Digbang\Security\Roles\Role $role */
            $options[$role->getRoleSlug()] = $role->getName();

            $rolePermissions[$role->getRoleSlug()] = $role->getPermissions()->map(function (Permission $permission) {
                return $permission->getName();
            })->toArray();
        }

        $inputs->dropdown(
            'roles',
            trans('backoffice::auth.roles'),
            $options,
            [
                'multiple'         => 'multiple',
                'class'            => 'user-groups form-control',
                'data-permissions' => json_encode($rolePermissions),
            ]
        );

        $permissions = $this->security()->permissions()->all();

        $inputs->dropdown(
            'permissions',
            trans('backoffice::auth.permissions'),
            $this->permissionParser()->toDropdownArray($permissions),
            [
                'multiple' => 'multiple',
                'class'    => 'multiselect',
            ]
        );

        return $form;
    }

    /**
     * @param $list
     */
    protected function buildFilters(Listing $list)
    {
        $filters = $list->filters();

        $filters->text('email',        trans('backoffice::auth.email'),      ['class' => 'form-control']);
        $filters->text('username',     trans('backoffice::auth.username'),   ['class' => 'form-control']);
        $filters->text('firstName',    trans('backoffice::auth.first_name'), ['class' => 'form-control']);
        $filters->text('lastName',     trans('backoffice::auth.last_name'),  ['class' => 'form-control']);
        $filters->boolean('activated', trans('backoffice::auth.activated'),  ['class' => 'form-control']);
    }

    /**
     * @return \Digbang\Backoffice\Listings\Listing
     */
    protected function getListing()
    {
        Carbon::setToStringFormat(trans('backoffice::default.datetime_format'));

        $listing = $this->backoffice()->listing([
            'firstName' => trans('backoffice::auth.first_name'),
            'lastName'  => trans('backoffice::auth.last_name'),
            'email'     => trans('backoffice::auth.email'),
            'username'  => trans('backoffice::auth.username'),
            'activated' => trans('backoffice::auth.activated'),
            'lastLogin' => trans('backoffice::auth.last_login'),
            'user_id', 'name', 'id',
        ]);

        $listing->columns()
            ->hide(['id', 'user_id', 'name'])
            ->sortable(array_keys($this->sortings));

        $listing->addValueExtractor('firstName', function (User $user) {
            return $user->getName()->getFirstName();
        });

        $listing->addValueExtractor('lastName', function (User $user) {
            return $user->getName()->getLastName();
        });

        $listing->addValueExtractor('id', function (User $user) {
            return $user->getUserId();
        });

        return $listing;
    }

    protected function buildListActions(Listing $list, Request $request)
    {
        $actions = $this->backoffice()->actions();

        try {
            $actions->link($this->url()->route(UserRoutes::CREATE), FontAwesome::icon('plus') . ' ' . trans('backoffice::default.new', ['model' => $this->title]), ['class' => 'btn btn-primary']);
        } catch (SecurityException $e) { /* Do nothing */
        }
        try {
            $actions->link($this->url()->route(UserRoutes::EXPORT, $request->all()), FontAwesome::icon('file-excel-o') . ' ' . trans('backoffice::default.export'), ['class' => 'btn btn-success']);
        } catch (SecurityException $e) { /* Do nothing */
        }

        $list->setActions($actions);

        $rowActions = $this->backoffice()->actions();

        // View icon
        $rowActions->link(function (Collection $row) {
            try {
                return $this->url()->route(UserRoutes::SHOW, $row['username']);
            } catch (SecurityException $e) {
                return false;
            }
        }, FontAwesome::icon('eye'), ['data-toggle' => 'tooltip', 'data-placement' => 'top', 'title' => trans('backoffice::default.show')]);

        // Edit icon
        $rowActions->link(function (Collection $row) {
            try {
                return $this->url()->route(UserRoutes::EDIT, $row['username']);
            } catch (SecurityException $e) {
                return false;
            }
        }, FontAwesome::icon('edit'), ['class' => 'text-success', 'data-toggle' => 'tooltip', 'data-placement' => 'top', 'title' => trans('backoffice::default.edit')]);

        // Delete icon
        $rowActions->form(
            function (Collection $row) {
                try {
                    return $this->url()->route(UserRoutes::DESTROY, $row['username']);
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

        $rowActions->form(
            function (Collection $row) {
                try {
                    return $this->url()->route(UserRoutes::RESET, $row['username']);
                } catch (SecurityException $e) {
                    return false;
                }
            },
            FontAwesome::icon('unlock-alt'),
            'POST',
            [
                'class'          => 'text-warning',
                'data-toggle'    => 'tooltip',
                'data-placement' => 'top',
                'data-confirm'   => trans('backoffice::auth.reset-password.confirm'),
                'title'          => trans('backoffice::auth.reset-password.title'),
            ]
        );

        $rowActions->form(
            function (Collection $row) {
                if ($row['activated']) {
                    return false;
                }
                try {
                    return $this->url()->route(UserRoutes::RESEND_ACTIVATION, $row['username']);
                } catch (SecurityException $e) {
                    return false;
                }
            },
            FontAwesome::icon('reply-all'),
            'POST',
            [
                'class'          => 'text-primary',
                'data-toggle'    => 'tooltip',
                'data-placement' => 'top',
                'data-confirm'   => trans('backoffice::auth.activation.confirm'),
                'title'          => trans('backoffice::auth.activation.title'),
            ]
        );

        $list->setRowActions($rowActions);
    }

    /**
     * @param Request $request
     * @param int     $limit
     *
     * @return array
     */
    protected function getData(Request $request, $limit = 10)
    {
        /** @var DoctrineUserRepository $users */
        $users = $this->security()->users();

        $filters = $request->only(['email', 'firstName', 'lastName', 'activated', 'username']);

        $filters = array_filter($filters, function ($field) {
            return $field !== null && $field !== '';
        });

        if (array_key_exists('activated', $filters)) {
            $filters['activated'] = $filters['activated'] == 'true';
        }

        return $users->search(
            $filters,
            $this->getSorting($request),
            $limit,
            ($request->input('page', 1) - 1) * $limit
        );
    }

    private function getSorting(Request $request)
    {
        $sortBy = $request->input('sort_by') ?: 'firstName';
        $sortSense = $request->input('sort_sense') ?: 'asc';

        return [
            $this->sortings[$sortBy] => $sortSense,
        ];
    }

    private function addRoles(User $user, array $roles)
    {
        if ($user instanceof Roleable && !empty($roles)) {
            /* @var Roleable $user */
            foreach ($roles as $role) {
                /** @var Role $role */
                if ($role = $this->security()->roles()->findBySlug($role)) {
                    $user->addRole($role);
                }
            }
        }
    }
}
