<?php

use Deploy\Account\User;

class BaseController extends Controller
{
    public function __construct()
    {
        View::share('loginUser', Sentry::loginUser());
    }
}
