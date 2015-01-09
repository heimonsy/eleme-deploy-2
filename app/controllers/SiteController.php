<?php

use Deploy\Site\Site;

class SiteController extends Controller
{
    public function index()
    {
        return Response::json(array(
            'code' => 0,
            'data' => Site::all()
        ));
    }

    public function show(Site $site)
    {
        return Response::json(array(
            'code' => 0,
            'data' => $site
        ));
    }

    public function store()
    {
        Input::merge(array_map('trim', Input::only('name', 'repo_git')));
        $validator = Validator::make(
            Input::only('name', 'repo_git'),
            array('name' => 'required|unique:sites,name', 'repo_git' => 'required'),
            array(
                'name.required' => '项目名不能为空',
                'name.unique' => '项目名已存在',
                'repo_git.required' => 'Fetch Url 不能为空',
            )
        );
        if ($validator->fails()) {
            $failed = $validator->failed();
            $fields = array();
            foreach ($failed as $key => $value) {
                $fields[] = $key;
            }

            return Response::json(array(
                'code' => 1,
                'msg' => $validator->messages()->all('<span>:message</span> <br>'),
                'fields' => $fields,
            ));
        }
        $site = new Site;
        $site->name = Input::get('name');
        $site->repo_git = Input::get('repo_git');
        $site->save();

        return Response::json(array(
            'code' => 0,
            'data' => [],
            'msg' => '创建成功',
        ));
    }

    public function update(Site $site)
    {
        Input::merge(array_map('trim', Input::only('name')));
        $validator = Validator::make(
            Input::only('name'),
            array('name' => 'required|unique:sites,name,' . $site->id),
            array(
                'name.required' => '项目名不能为空',
                'name.unique' => '项目名已存在',
            )
        );

        if ($validator->fails()) {
            $failed = $validator->failed();
            $fields = array();
            foreach ($failed as $key => $value) {
                $fields[] = $key;
            }

            return Response::json(array(
                'code' => 1,
                'msg' => $validator->messages()->all('<span>:message</span> <br>'),
                'fields' => $fields,
            ));
        }

        $site->name = Input::get('name');
        $site->save();

        return Response::json(array(
            'code' => 0,
            'msg' => '保存成功',
        ));
    }

    public function destroy(Site $site)
    {
        $site->delete();
        return Response::json(array(
            'code' => 0,
            'msg' => '删除成功',
        ));
    }
}
