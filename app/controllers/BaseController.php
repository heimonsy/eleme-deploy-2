<?php

use Deploy\Account\User;
use Deploy\Site\Site;

class BaseController extends Controller
{
    public function __construct()
    {
        View::share('siteList', Site::select('id', 'name')->get());
        View::share('loginUser', Sentry::loginUser());
    }
}
