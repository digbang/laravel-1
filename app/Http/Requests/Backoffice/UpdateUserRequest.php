<?php
namespace App\Http\Requests\Backoffice;

use App\Http\Routes\Backoffice\UserBindings;
use Digbang\Security\Users\DefaultUser;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    use ValidatesUsers {
        rules as defaultRules;
    }

    /**
     * Exclude this user from the unique validation.
     *
     * {@inheritdoc}
     */
    public function rules()
    {
        $rules = $this->defaultRules();

        /** @var DefaultUser $user */
        $user = $this->route(UserBindings::USERNAME);

        $rules['email']    .= ','.$user->getUserId();
        $rules['username'] .= ','.$user->getUserId();

        return $rules;
    }
}
