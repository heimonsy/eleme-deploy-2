<?php

use Deploy\Hosts\HostTypeCatalog;

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
        $catalog->save();

        return Response::json(array(
            'code' => 0,
            'data' => [],
            'msg' => '保存成功'
        ));
    }

    public function destroy(HostTypeCatalog $catalog)
    {
        $catalog->delete();
        return Response::json(array(
            'code' => 0,
            'data' => [],
            'msg' => '删除成功',
        ));
    }
}
