<?php
namespace App\Http\Requests\Backoffice;

use App\Http\Requests\Request;

class ResendActivationRequest extends Request
{
	public function rules()
	{
		return [
			'email' => 'required|email'
		];
	}

	public function messages()
	{
		return [
			'email' => trans('backoffice::auth.validation.activation.email')
		];
	}
}