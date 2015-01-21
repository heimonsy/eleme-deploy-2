<?php

use Deploy\Account\Role;
use Deploy\Sentry\Permission;
use Deploy\Site\Site;
use Deploy\Site\DeployConfig;
use Deploy\Hosts\HostTypeCatalog;
use Deploy\Account\User;
use Deploy\Facade\Worker;
use Deploy\Worker\Job;
use Deploy\Site\PullRequestBuild;


class SiteCommitController extends Controller
{
    public function index(Site $site)
    {
        return Response::json(array(
            'code' => 0,
            'data' => $site->commits()->orderBy('id', 'desc')->get()
        ));
    }
}
