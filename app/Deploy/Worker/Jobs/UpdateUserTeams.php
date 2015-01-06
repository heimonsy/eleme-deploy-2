<?php
namespace Deploy\Worker\Jobs;

use Deploy\Worker\Task;
use Deploy\Github\GithubClient;
use Exception;
use Log;
use Deploy\Account\User;
use Config;
use Deploy\Worker\Job;
use Deploy\Account\Team;
use Worker;

class UpdateUserTeams extends Task
{
    public function fire($worker)
    {
        $ids = $this->message['id'];
        if ($ids == 'all') {
            $users = User::normal()->get();
        } else {
            if (!is_array($ids)) {
                $ids = array($ids);
            }
            if (isset($this->message['status'])) {
                $users = User::whereIn('id', $ids)->where('status', '=', $this->message['status'])->get();
            } else {
                $users = User::whereIn('id', $ids)->normal()->get();
            }
        }

        try {
            $organization = Config::get('github.organization');
            foreach ($users as $user) {
                $client = new \Github\Client();
                $client->authenticate($user->token, null, \Github\Client::AUTH_HTTP_TOKEN);

                $userApi = new \Deploy\Github\CurrentUser($client);
                $paginator  = new \Github\ResultPager($client);
                $teams = $paginator->fetchAll($userApi, 'teams');

                $teamIds = array();
                foreach ($teams as $team) {
                    if ($team['organization']['login'] == $organization) {
                        $teamObj = Team::firstOrNew(array('id' => $team['id']));
                        //$teamObj->id = $team['id']
                        $teamObj->name = $team['name'];
                        $teamObj->permission = $team['permission'];
                        $teamObj->description = $team['description'];
                        $updateAt = $teamObj->updated_at;
                        $teamObj->save();
                        $teamIds[] = $team['id'];

                        if (!$updateAt || time() - strtotime($updateAt) > 60 * 60 * 24) {
                            Worker::push('Deploy\Worker\Jobs\UpdateTeamRepos', "update team [{$team['id']}] repos", Job::TYPE_SYSTEM, array(
                                'teamId' => $team['id'],
                                'userId' => $user->id,
                            ));
                        }
                    }
                }

                Log::info(json_encode($teamIds));
                $user->teams()->sync($teamIds);

                if ($user->isWaiting()) {
                    $user->status = User::STATUS_NORMAL;
                    $user->save();
                }
            }
            $worker->deleteJob();
        } catch (Exception $e) {
            Log::error($e);
            $worker->deleteJob(Job::STATUS_ERROR);
            // todo release job 或者 将用户的状态设置为出错
        }
    }
}
