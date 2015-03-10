<?php

namespace Deploy\Worker\Tasks;

use Deploy\Worker\Task;
use Deploy\Site\Deploy;
use Deploy\Hosts\Host;
use SSHProcess\RsyncProcess;
use Deploy\Worker\DeployHost;
use Deploy\Site\Site;
use Deploy\Site\DeployConfig;
use Log;
use Deploy\Worker\DeployScript;
use Eleme\Rlock\Lock;
use Deploy\Locks\JobLock;
use Heimonsy\HipChat;

class DeployNotify extends Task
{
    public function fire($worker)
    {
        $STATUS = $this->message['status'];
        $jobId = $this->message['job_id'];

        $site = Site::with('deploy_config')->findOrFail($this->message['site_id']);
        $config = DeployConfig::firstOrCreate(array('site_id' => $site->id));
        $deploy = Deploy::findOrFail($this->message['deploy_id']);
        $user = $deploy->user()->first();
        $prevDeploy = $deploy->prevDeploy();

        $COMMIT = $deploy->commit;
        $PREV_COMMIT = $prevDeploy == null ? null : $prevDeploy->commit;
        $JOB_URL = url('site/' . $site->id .'#JobHost-' . $jobId . '-nj');
        $HIPCHAT_TOKEN = $site->realHipchatToken();
        $HIPCHAT_ROOM = $site->hipchat_room;
        $REPO_NAME = $site->repoName();

        $LOG_PREFIX = "[Send Deploy Notify {$site->name}]";

        $DIFF_MSG = '';
        $DIFF_URL = '';
        if (!empty($PREV_COMMIT) && $PREV_COMMIT != $COMMIT ) {
            $DIFF_URL = "https://github.com/{$REPO_NAME}/compare/{$PREV_COMMIT}...{$COMMIT}";
            $DIFF_MSG = 'Diff: ' . $DIFF_URL;
        }

        try {
            Log::info("{$LOG_PREFIX} Start");
            if (!empty($HIPCHAT_TOKEN) && !empty($HIPCHAT_ROOM)) {
                $client = new HipChat($HIPCHAT_TOKEN, $HIPCHAT_ROOM);
                                $HIPCHAT_MSG = <<<EOT
Message: Deploy {$site->name} Success
Hosts: [Total {$deploy->total_hosts}] [Success {$deploy->success_hosts}] [Error {$deploy->error_hosts}]
Job Url: {$JOB_URL}
Operater: {$user->name}
Status: {$STATUS}
Commit: {$COMMIT}
{$DIFF_MSG}
EOT;
                    $client->notify($HIPCHAT_MSG);
                    Log::info("{$LOG_PREFIX} Hipchat Msg Send Sucess");
            }

        } catch (Exception $e){
            Log::info($e);
            Log::info("{$LOG_PREFIX} Hipchat Send Error");
        }

        try {
            $watchers = $site->watchers()->get()->toArray();
            $emails = $config->notify_emails == '' ? array() : explode(';', $config->notify_emails);
            foreach ($watchers as $watcher) {
                $emails[] = $watcher['notify_email'];
            }

            $emails = array_unique($emails);
            if (count($emails) > 0) {
                $mailer = \Mail::getSwiftMailer();
                $transport = $mailer->getTransport();
                $transport->stop();
                $transport->start();

                $data = array(
                    'site' => $site,
                    'status' => $STATUS,
                    'deploy' => $deploy,
                    'user' => $user,
                    'repoName' => $site->repoName(),
                    'jobUrl' => $JOB_URL,
                    'diffUrl' => $DIFF_URL
                );

                \Mail::send('layout.notify', $data, function($message) use ($emails, $STATUS) {
                    $email = array_pop($emails);
                    $message->to($email)->subject('Deploy ' . $STATUS . '!');
                    foreach ($emails as $email) {
                        $message->cc($email);
                    }
                });
                Log::info("{$LOG_PREFIX} Send Email Success");
            }
        } catch (Exception $e) {
            Log::info($e);
            Log::info("{$LOG_PREFIX} Email Send Error");
        }
        Log::info("{$LOG_PREFIX} Finish");
        $worker->deleteJob();
    }
}
