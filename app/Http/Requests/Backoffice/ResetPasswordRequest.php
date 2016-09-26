<?php
namespace App\Http\Requests\Backoffice;

use App\Http\Requests\Request;

class ResetPasswordRequest extends Request
{
	public function rules()
	{
		return [
			'password' => 'required|confirmed'
		];
	}

	public function messages()
	{
		return [
			'password' => trans('backoffice::auth.validation.reset-password.confirmation')
		];
	}
}