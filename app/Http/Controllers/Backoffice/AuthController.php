<?php
namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\LoginRequest;
use App\Http\Requests\Backoffice\ResendActivationRequest;
use App\Http\Requests\Backoffice\ResetPasswordRequest;
use App\Http\Routes\Backoffice\AuthRoutes;
use App\Http\Routes\Backoffice\DashboardRoutes;
use Carbon\Carbon;
use Cartalyst\Sentinel\Checkpoints\NotActivatedException;
use Cartalyst\Sentinel\Checkpoints\ThrottlingException;
use Digbang\Security\Activations\Activation;
use Digbang\Security\Contracts\SecurityApi;
use Digbang\Security\Users\User;
use Illuminate\Config\Repository;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\MessageBag;
use Illuminate\View\Factory as View;

class AuthController extends Controller
{
    use SendsEmails;

    /**
     * @var Redirector
     */
    protected $redirector;

    /**
     * @var Repository
     */
    private $config;

    /**
     * @param Redirector $redirector
     * @param Repository $config
     */
    public function __construct(Redirector $redirector, Repository $config)
    {
        $this->redirector = $redirector;
        $this->config = $config;
    }

    /**
     * @param View $view
     *
     * @return \Illuminate\View\View
     */
    public function login(View $view)
    {
        return $view->make('backoffice::auth.login');
    }

    /**
     * @param LoginRequest $request
     * @param SecurityApi  $securityApi
     * @param View         $view
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function authenticate(LoginRequest $request, SecurityApi $securityApi, View $view)
    {
        $errors = new MessageBag();

        try {
            $authenticated = $securityApi->authenticate(
                $request->only(['email', 'username', 'login', 'password']),
                $request->input('remember'));

            if ($authenticated) {
                return $this->redirector->intended(
                    $securityApi->url()->route(DashboardRoutes::HOME)
                );
            }

            $errors->add('password', trans('backoffice::auth.validation.password.wrong'));

            return $this->redirector->route(AuthRoutes::LOGIN)->withInput()->withErrors($errors);
        } catch (ThrottlingException $e) {
            return $view->make('backoffice::auth.throttling', [
                'message' => trans('backoffice::auth.throttling.' . $e->getType(), ['remaining' => Carbon::now()->diffInSeconds($e->getFree())]),
            ]);
        } catch (NotActivatedException $e) {
            return $view->make('backoffice::auth.not-activated');
        }
    }

    /**
     * @param View $view
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function resendActivationForm(View $view)
    {
        return $view->make('backoffice::auth.request-activation');
    }

    /**
     * @param ResendActivationRequest $request
     * @param SecurityApi             $securityApi
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function resendActivationRequest(ResendActivationRequest $request, SecurityApi $securityApi)
    {
        $email = $request->input('email');

        /** @var User $user */
        $user = $securityApi->users()->findByCredentials(['email' => $email]);

        if (!$user) {
            $this->redirector->back()->withInput()->withErrors([
                'email' => trans('backoffice::auth.validation.activation.incorrect', $email),
            ]);
        }

        $activations = $securityApi->activations();

        /** @var Activation $activation */
        $activation = $activations->exists($user) ?: $activations->create($user);

        $this->sendActivation($user, route(
            AuthRoutes::ACTIVATE, [
            $user->getUserId(), $activation->getCode(),
        ]));

        return $this->redirector->route(AuthRoutes::LOGIN)->with(
            'success', trans('backoffice::auth.activation.email-sent')
        );
    }

    /**
     * @param SecurityApi $securityApi
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(SecurityApi $securityApi)
    {
        $securityApi->logout();

        return $this->redirector->route(AuthRoutes::LOGIN);
    }

    /**
     * @param User        $user
     * @param string      $activationCode
     * @param View        $view
     * @param SecurityApi $securityApi
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function activate(User $user, $activationCode, View $view, SecurityApi $securityApi)
    {
        $activations = $securityApi->activations();

        if ($activations->completed($user)) {
            return $this->redirector->route(AuthRoutes::LOGIN)
                ->with('warning', trans('backoffice::auth.validation.user.already-active'));
        }

        if ($activations->exists($user, $activationCode)) {
            $activations->complete($user, $activationCode);

            return $this->redirector->route(AuthRoutes::LOGIN)
                ->with('success', trans('backoffice::auth.activation.success'));
        }

        return $view->make('backoffice::auth.activation-expired', [
            'email' => $this->config->get('backoffice.auth.contact'),
        ]);
    }

    /**
     * @param User        $user
     * @param string      $resetCode
     * @param SecurityApi $securityApi
     * @param View        $view
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function resetPassword(User $user, $resetCode, SecurityApi $securityApi, View $view)
    {
        if ($securityApi->reminders()->exists($user, $resetCode)) {
            return $view->make('backoffice::auth.reset-password', [
                'id'        => $user->getUserId(),
                'resetCode' => $resetCode,
            ]);
        }

        return $this->redirector->route(AuthRoutes::LOGIN)
            ->with('danger', trans('backoffice::auth.validation.reset-password.incorrect'));
    }

    /**
     * @param User                 $user
     * @param string               $resetCode
     * @param ResetPasswordRequest $request
     * @param SecurityApi          $securityApi
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function resetPasswordRequest($user, $resetCode, ResetPasswordRequest $request, SecurityApi $securityApi)
    {
        if ($user->getUserId() != $request->input('id')) {
            return $this->redirector->route(AuthRoutes::LOGIN);
        }

        $reminders = $securityApi->reminders();

        if ($reminders->exists($user, $resetCode)) {
            $reminders->complete($user, $resetCode, $request->input('password'));

            return $this->redirector->route(AuthRoutes::LOGIN)->with(
                'success', trans('backoffice::auth.reset-password.success', ['email' => $user->getEmail()])
            );
        }

        return $this->redirector->route(AuthRoutes::LOGIN)
            ->with('danger', trans('backoffice::auth.validation.reset-password.incorrect'));
    }

    /**
     * @param View $view
     *
     * @return \Illuminate\View\View
     */
    public function forgotPassword(View $view)
    {
        return $view->make('backoffice::auth.request-reset-password');
    }

    /**
     * @param Request     $request
     * @param SecurityApi $securityApi
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function forgotPasswordRequest(Request $request, SecurityApi $securityApi)
    {
        $email = trim($request->input('email'));

        /** @var \Digbang\Security\Users\User $user */
        if (!$email || !($user = $securityApi->users()->findByCredentials(['email' => $email]))) {
            return $this->redirector->back()
                ->withErrors(['email' => trans('backoffice::auth.validation.user.not-found')]);
        }

        /** @var \Digbang\Security\Reminders\Reminder $reminder */
        $reminder = $securityApi->reminders()->create($user);

        $this->sendPasswordReset(
            $user,
            route(AuthRoutes::RESET, [$user->getUserId(), $reminder->getCode()])
        );

        return $this->redirector->route(AuthRoutes::LOGIN)
            ->with('info', trans('backoffice::auth.reset-password.email-sent',
                ['email' => $user->getEmail()]
            ));
    }
}
