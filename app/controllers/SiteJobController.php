<?php

use Deploy\Site\Site;
use Deploy\Worker\Job;


class SiteJobController extends Controller
{
    public function show(Site $site, Job $job)
    {
        return Response::json(array(
            'code' => 0,
            'data' => $job,
        ));
    }
}
