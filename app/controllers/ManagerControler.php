<?php

use Deploy\Account\User;
use Deploy\Worker\Job;

class ManagerController extends Controller
{
    public function role()
    {
        return Response::view('manager.role');
    }
}
