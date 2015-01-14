<?php

use Deploy\Hosts\HostType;
use Deploy\Hosts\Host;
use Deploy\Site\Site;
use Deploy\Hosts\HostTypeCatalog;

class SiteHostController extends Controller
{
    public function index(Site $site)
    {
        return Response::json(array(
            'code' => 0,
            'data' => Host::where('site_id', '=', $site->id)->with('host_type')->get()
        ));
    }

    public function store(Site $site)
    {
        Input::merge(array_map('trim', Input::only('name', 'host_type_id', 'ip', 'port', 'type')));
        $validator = Validator::make(
            Input::only('name', 'host_type_id', 'port', 'ip', 'type'),
            array(
                'name' => 'required|unique:hosts,name',
                'host_type_id' => 'required|exists:host_types,id',
                'ip' => 'required|ip',
                'port' => 'required|numeric',
                'type' => 'required|in:APP,STATIC',
            ),
            array(
                'required' => '字段不能为空',
                'name.unique' => '主机名已存在',
                'catalog_id.exists' => '环境类型不存在',
                'ip' => 'IP格式不正确',
                'port.numeric' => '端口必须为数字',
                'in' => '主机发布类型不正确',
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

        $host = new Host;
        $host->fill(Input::only('name', 'ip', 'port', 'type', 'host_type_id'));
        $host->site()->associate($site);
        $host->save();

        return Response::json(array(
            'code' => 0,
            'msg' => '成功新建主机',
        ));
    }

    public function update(Site $site, Host $host)
    {
        Input::merge(array_map('trim', Input::only('name', 'host_type_id', 'ip', 'port', 'type')));
        $validator = Validator::make(
            Input::only('name', 'host_type_id', 'port', 'ip', 'type'),
            array(
                'name' => 'required|unique:hosts,name,' . $host->id,
                'host_type_id' => 'required|exists:host_types,id',
                'ip' => 'required|ip',
                'port' => 'required|numeric',
                'type' => 'required|in:APP,STATIC',
            ),
            array(
                'required' => '字段不能为空',
                'name.unique' => '主机名已存在',
                'catalog_id.exists' => '环境类型不存在',
                'ip' => 'IP格式不正确',
                'port.numeric' => '端口必须为数字',
                'in' => '主机发布类型不正确',
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

        $host->fill(Input::only('name', 'host_type_id', 'port', 'ip', 'type'));
        $host->save();

        return Response::json(array(
            'code' => 0,
            'msg' => '保存成功'
        ));
    }

    public function destroy(Site $site, Host $hosttype)
    {
        $hosttype->delete();

        return Response::json(array(
            'code' => 0,
            'msg' => '删除成功',
        ));
    }
}
