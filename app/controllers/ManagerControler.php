<?php

use Deploy\Account\User;
use Deploy\Worker\Job;
use Deploy\Site\Site;

class ManagerController extends BaseController
{
    public function configure()
    {
        return Response::view('manager.configure');
    }

    public function role()
    {
        return Response::view('manager.role');
    }

    public function hosttypecatalogs()
    {
        return Response::view('manager.hosttypecatalogs');
    }

    public function users()
    {
        return Response::view('manager.users');
    }

    public function site(Site $site)
    {
        return Response::view('manager.site', array(
            'site' => $site
        ));
    }

    public function sites()
    {
        return Response::view('manager.sites');
    }
}
