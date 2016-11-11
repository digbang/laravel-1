<?php
namespace App\Http\Requests\Backoffice;

use Illuminate\Foundation\Http\FormRequest;

class CreateUserRequest extends FormRequest
{
    use ValidatesUsers {
        rules as defaultRules;
    }

    public function rules()
    {
        $rules = $this->defaultRules();
        $rules['password'] = 'required|'.$rules['password'];

        return $rules;
    }
}
