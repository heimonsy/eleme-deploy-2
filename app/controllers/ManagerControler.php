<?php

use Deploy\Account\User;
use Deploy\Worker\Job;
use Deploy\Site\Site;
use Deploy\Hosts\Host;

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
        $user = \Sentry::loginUser();
        $canManager = $user->control($site->manageAction());
        $isWatching = $user->watchs->contains($site->id);

        return Response::view('manager.site', array(
            'site' => $site,
            'payload_url' => url('payload/site/' . $site->id),
            'can_manage' => $canManager,
            'isWatching' => $isWatching
        ));
    }

    public function sites()
    {
        return Response::view('manager.sites');
    }

    public function hosts(Site $site)
    {
        $hosts = Host::where('site_id', '=', $site->id)->with('host_type')->orderBy('type')->orderBy('name')->get();
        $prevType = '';
        foreach ($hosts as $host) {
            if ($host->host_type->name !== $prevType) {
                echo "\n<br>\n";
            }
            echo "{$host->host_type->name} {$host->type} {$host->name} {$host->ip} {$host->port}<br>";
            $prevType = $host->host_type->name;
        }
        return '';
    }
}
