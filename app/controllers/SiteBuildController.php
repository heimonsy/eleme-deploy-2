<?php

use Deploy\Site\Build;
use Deploy\Site\Site;
use Deploy\Facade\Worker;

class SiteBuildController extends Controller
{
    public function index(Site $site)
    {
        return Response::json(array(
            'code' => 0,
            'data' => Build::of($site)->with(array('user' => function ($query) {
                $query->select('name', 'login', 'id');
            }))->orderBy('id', 'desc')->limit(20)->get()
        ));
    }

    public function store(Site $site)
    {
        $checkout = trim(Input::get('checkout', ''));
        if (empty($checkout)) {
            return Response::json(array(
                'code' => 1,
                'msg' => 'checkout 不能为空',
            ));
        }
        $user = Sentry::loginUser();

        $job = Worker::createJob(
            'Deploy\Worker\Jobs\BuildRepo',
            "操作：Build &nbsp; " . "项目：{$site->name} &nbsp;" . "操作者：{$user->name}({$user->login}) &nbsp;"
        );

        $build = new Build;
        $build->checkout = $checkout;
        $build->status = Build::STATUS_WAITING;
        $build->status_info = '正在等待';
        $build->job()->associate($job);
        $build->site()->associate($site);
        $build->user()->associate($user);
        $build->save();

        $job->message = array(
            'build_id' => $build->id,
            'site_id' => $site->id,
        );
        Worker::push($job);

        return Response::json(array(
            'code' => 0,
            'msg' => '成功新建Build任务',
            'data' => array(
                'jobId' => $job->id
            )
        ));
    }
}
