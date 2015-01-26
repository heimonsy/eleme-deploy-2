<?php

use Deploy\Worker\DeployHost;
use Deploy\Site\Site;
use Deploy\Worker\SampleTask;

class DeployHostController extends Controller
{
    public function show(Site $site, DeployHost $host)
    {
        if (empty($host->task_id)) {
            return Response::json(array(
                'code' => 0,
                'data' => $data
            ));
        }

        $task = SampleTask::find($host->task_id);
        $data = $host->toArray();
        $data['output'] = $task->getOutput();

        return Response::json(array(
            'code' => 0,
            'data' => $data
        ));
    }
}
