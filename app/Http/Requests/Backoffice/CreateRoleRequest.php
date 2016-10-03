<?php
namespace App\Http\Requests\Backoffice;

use App\Http\Requests\Request;

class CreateRoleRequest extends Request
{
    public function rules()
    {
        return ['name' => 'required'];
    }

    public function messages()
    {
        return [
            'name.required' => trans('backoffice::auth.validation.group.name'),
        ];
    }
}
