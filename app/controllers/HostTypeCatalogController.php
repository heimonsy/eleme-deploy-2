<?php

use Deploy\Hosts\HostTypeCatalog;
use Deploy\Hosts\HostType;

class HostTypeCatalogController extends Controller
{
    public function index()
    {
        return Response::json(array(
            'code' => 0,
            'data' => HostTypeCatalog::all(),
            'msg' => 'success'
        ));
    }

    public function show(HostTypeCatalog $catalog) {
        return Response::json(array(
            'code' => 0,
            'data' => $catalog
        ));
    }

    public function store()
    {
        Input::merge(array_map('trim', Input::only('name')));
        $validator = Validator::make(
            Input::only('name'),
            array('name' => 'required|unique:host_type_catalogs,name'),
            array(
                'name.required' => '发布环境名称不能为空',
                'name.unique' => '发布环境名称已存在',
            )
        );

        if ($validator->fails()) {
            return Response::json(array(
                'code' => 1,
                'msg' => $validator->messages()->all('<span>:message</span> <br>'),
                'fields' => array(
                    'name'
                ),
            ));
        }

        $m = new HostTypeCatalog;
        $m->name = Input::get('name');
        $m->is_send_notify = Input::get('is_send_notify');
        $m->save();

        return Response::json(array(
            'code' => 0,
            'data' => [],
            'msg' => '创建成功'
        ));
    }

    public function update(HostTypeCatalog $catalog)
    {
        Input::merge(array_map('trim', Input::only('name')));

        $validator = Validator::make(
            Input::only('name'),
            array('name' => 'required|unique:host_type_catalogs,name,' . $catalog->id),
            array(
                'name.required' => '发布环境名称不能为空',
                'name.unique' => '发布环境名称已存在',
            )
        );

        if ($validator->fails()) {
            return Response::json(array(
                'code' => 1,
                'msg' => $validator->messages()->all('<span>:message</span> <br>'),
                'fields' => array(
                    'name'
                ),
            ));
        }

        $catalog->name = Input::get('name');
        $catalog->is_send_notify = Input::get('is_send_notify');
        $catalog->save();

        return Response::json(array(
            'code' => 0,
            'data' => [],
            'msg' => '保存成功'
        ));
    }

    public function destroy(HostTypeCatalog $catalog)
    {
        $count = HostType::where('catalog_id', $catalog->id)->count();
        if ($count > 0) {
            return Response::json(array(
                'code' => 1,
                'data' => [],
                'msg' => '请先删除该环境下的机器分组',
            ));
        }

        $catalog->delete();
        return Response::json(array(
            'code' => 0,
            'data' => [],
            'msg' => '删除成功',
        ));
    }
}
