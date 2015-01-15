<?php

use Deploy\Site\Build;
use Deploy\Site\Site;

class SiteBuildController extends Controller
{
    public function index(Site $site)
    {
        return Response::json(array(
            'code' => 0,
            'data' => Build::of($site)->with(array('user' => function ($query) {
                $query->select('name', 'login', 'id');
            }))->limit(20)->get()
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
        $build = new Build;
        $build->checkout = $checkout;
        $build->status = Build::STATUS_WAITING;
        $build->status_info = '正在等待';
        $build->site()->associate($site);
        $build->user()->associate(Sentry::loginUser());
        $build->save();

        return Response::json(array(
            'code' => 0,
            'msg' => '成功新建Build任务'
        ));
    }
}
