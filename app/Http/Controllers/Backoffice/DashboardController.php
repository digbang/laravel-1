<?php namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use Digbang\Security\Contracts\SecurityApi;
use Illuminate\View\Factory;

class DashboardController extends Controller
{
	public function dashboard(Factory $view, SecurityApi $securityApi)
	{
		return $view->make('backoffice::empty', [
			'user' => $securityApi->getUser()
		]);
	}
}
