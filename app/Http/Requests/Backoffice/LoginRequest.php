<?php
namespace App\Http\Requests\Backoffice;

use App\Http\Requests\Request;

class LoginRequest extends Request
{
	public function rules()
	{
		return [
			'email'    => 'required_without:login|email',
			'username' => 'required_without:login',
			'login'    => 'required_without:email,username',
			'password' => 'required'
		];
	}
}