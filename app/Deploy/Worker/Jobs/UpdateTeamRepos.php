<?php
namespace Deploy\Worker\Jobs;

use Deploy\Worker\Task;
use Exception;
use Log;
use Deploy\Account\User;
use Config;
use Deploy\Worker\Job;
use Deploy\Account\Team;
use Github\Api\Organization\Teams as TeamApi;
use Github\ResultPager;
use Github\Client;
use Deploy\Account\Repo;

class UpdateTeamRepos extends Task
{
    public function fire($worker)
    {
        $team = Team::find($this->job->message['teamId']);
        $user = User::find($this->job->message['userId']);

        $client = new Client();
        $client->authenticate($user->token, null, \Github\Client::AUTH_HTTP_TOKEN);
        $teamApi = new TeamApi($client);
        $paginator  = new ResultPager($client);
        $repos = $paginator->fetchAll($teamApi, 'repositories', array($team->id));

        $repoIds = array();
        foreach ($repos as $m) {
            $repo = Repo::firstOrNew(array('id' => $m['id']));
            $repo->name = $m['name'];
            $repo->full_name = $m['full_name'];
            $repo->description = $m['description'];
            $repo->save();
            $repoIds[] = $m['id'];
        }
        $team->repos()->sync($repoIds);
        $team->touch();
    }
}
