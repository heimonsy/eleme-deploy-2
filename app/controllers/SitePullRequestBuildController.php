<?php

use Deploy\Site\PullRequestBuild;
use Deploy\Site\Site;
use Deploy\Facade\Worker;
use Deploy\Worker\Job;

class SitePullRequestBuildController extends Controller
{
    public function index(Site $site)
    {
        return Response::json(array(
            'code' => 0,
            'data' => PullRequestBuild::of($site)->open()->orderBy('id', 'desc')->limit(30)->get()
        ));
    }

    public function store(Site $site)
    {
        if (Request::header('X-GitHub-Event') == 'pull_request') {
            $info = json_decode(file_get_contents('php://input'));
            $data = array();
            $data['pull_request_id'] = $info->pull_request->id;
            $data['commit'] = $info->pull_request->head->sha;
            $count = PullRequestBuild::where($data)->count();
            if ($count >= 1 ) {
                if ($info->action == PullRequestBuild::PR_STATUS_CLOSED) {
                    PullRequestBuild::where('pull_request_id', $data['pull_request_id'])->update(array(
                        'status' => PullRequestBuild::PR_STATUS_CLOSED,
                        'merged_by' => $info->pull_request->merged_by->login
                    ));
                } elseif ($info->action == 'reopened') {
                    PullRequestBuild::where('pull_request_id', $data['pull_request_id'])->update(array(
                        'status' => PullRequestBuild::PR_STATUS_OPEN,
                    ));
                }
            } else {
                $job = Worker::createJob(
                    'Deploy\Worker\Jobs\BuildPullRequest',
                    "操作：PR Build &nbsp; " . "项目：{$site->name} &nbsp;" . "操作者：Hook &nbsp;",
                    array(),
                    Job::TYPE_SYSTEM
                );

                $data['site_id'] = $site->id;
                $data['job_id'] = $job->id;
                $data['title'] = $info->pull_request->title;
                $data['number'] = $info->pull_request->number;
                $data['repo_name'] = $info->pull_request->base->repo->full_name;
                $data['user_login'] = $info->pull_request->user->login;
                $data['status'] = PullRequestBuild::PR_STATUS_OPEN;
                $data['build_status'] = PullRequestBuild::STATUS_WAITING;
                $data['test_status'] = PullRequestBuild::STATUS_WAITING;
                $build = new PullRequestBuild;
                $build->fill($data);
                $build->save();

                $job->message = array('site_id' => $site->id, 'pr_id' => $build->id);
                Worker::push($job);
            }
        }
        return Response::make('ok');
    }
}
