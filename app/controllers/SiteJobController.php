<?php

use Deploy\Site\Site;
use Deploy\Worker\Job;
use Deploy\Worker\DeployHost;
use Deploy\Site\Deploy;
use Deploy\Worker\WorkableInterface;


class SiteJobController extends Controller
{
    public function show(Site $site, Job $job)
    {
        $response = array(
            'code' => 0,
            'data' => array(
                'job' => $job,
            ),
        );

        $type = Input::get('type', 'build');

        if ($type == 'deploy') {
            $response['data']['hosts'] = DeployHost::where('job_id', $job->id)->get();
            $response['data']['deploy'] = $deploy = Deploy::where('job_id', $job->id)->first();
            $response['status'] = $deploy->isSuccess() && $job->isSuccess() && ($deploy->total_hosts <= $deploy->success_hosts + $deploy->error_hosts) ?
                'Finish' : 'Doing';
        } else {
            $response['status'] = $job->status != WorkableInterface::STATUS_ERROR && $job->status != WorkableInterface::STATUS_SUCCESS ? 'Doing' : 'Finish';
        }

        return Response::json($response);
    }
}
