<?php
namespace Deploy\Worker\Tasks;

use Deploy\Worker\Task;
use Deploy\Site\Deploy;
use Deploy\Site\Site;
use Log;
use Deploy\Site\PullRequestBuild;
use Deploy\Github\GithubClient;

class PRStatusNotify extends Task
{
    public function fire($worker)
    {
        $site = Site::with('deploy_config')->findOrFail($this->message['site_id']);
        $pr = PullRequestBuild::findOrFail($this->message['pr_id']);

        $STATUS = $this->message['status'];
        $DESCRIPTION = $this->message['description'];
        $JOB_ID = $this->message['job_id'];

        $REPO_NAME = $pr->repo_name;
        $COMMIT = $pr->commit;
        $TOKEN = $site->realGithubToken();

        $LOG_PREFIX = "[Send PR Notify {$site->name} $pr->number]";

        $PROXY = Config::get('github.proxy');

        try {
            if (!empty($TOKEN)) {
                Log::info("{$LOG_PREFIX} Start");
                $client = new GithubClient($TOKEN, $PROXY);

                $response = $client->request("repos/{$REPO_NAME}/statuses/{$COMMIT}", json_encode(array(
                    'state' => $STATUS,
                    "target_url" => url("site/{$site->id}#JobInfo-{$JOB_ID}-nj"),
                    "description" => $DESCRIPTION,
                    "context" => "eleme deploy 2"
                )), 'POST');
                Log::info("{$LOG_PREFIX} Success");
            }
        } catch (Exception $e) {
            Log::info($e);
            Log::info($e->getResponse()->getBody(true));
            Log::info("Send Status Error");
        }
    }
}
