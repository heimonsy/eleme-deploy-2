<?php

use Deploy\Hosts\HostType;
use Deploy\Site\Site;
use Deploy\Hosts\HostTypeCatalog;

class SiteHostTypeController extends Controller
{
    public function index(Site $site)
    {
        $hostTypes = HostType::where('site_id', $site->id)->with('catalog')->get();

        return Response::json(array(
            'code' => 0,
            'data' => $hostTypes
        ));
    }

    public function store(Site $site)
    {
        Input::merge(array_map('trim', Input::only('name', 'catalog_id')));
        $validator = Validator::make(
            Input::only('name', 'catalog_id'),
            array('name' => 'required|unique:host_types,name,null,id,site_id,' . $site->id, 'catalog_id' => 'required|exists:host_type_catalogs,id'),
            array(
                'name.required' => '机器分组名不能为空',
                'name.unique' => '机器分组名已存在',
                'catalog_id.required' => '环境类型不能为空',
                'catalog_id.exists' => '环境类型不存在',
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

        $catalog = HostTypeCatalog::find(Input::get('catalog_id'));
        $hosttype = new HostType;
        $hosttype->name = Input::get('name');
        $hosttype->site()->associate($site);
        $hosttype->catalog()->associate($catalog);
        $hosttype->save();

        return Response::json(array(
            'code' => 0,
            'msg' => '新建成功',
        ));
    }

    public function update(Site $site, HostType $hosttype)
    {
        Input::merge(array_map('trim', Input::only('name', 'catalog_id')));
        $validator = Validator::make(
            Input::only('name', 'catalog_id'),
            array('name' => 'required|unique:host_types,name,' . $hosttype->id . ',id,site_id,' . $site->id, 'catalog_id' => 'required|exists:host_type_catalogs,id'),
            array(
                'name.required' => '机器分组名不能为空',
                'name.unique' => '机器分组名已存在',
                'catalog_id.required' => '环境类型不能为空',
                'catalog_id.exists' => '环境类型不存在',
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

        $catalog = HostTypeCatalog::find(Input::get('catalog_id'));
        $hosttype->name = Input::get('name');
        $hosttype->catalog()->associate($catalog);
        $hosttype->save();

        return Response::json(array(
            'code' => 0,
            'msg' => '修改成功'
        ));
    }

    public function destroy(Site $site, HostType $hosttype)
    {
        $hosttype->delete();
        // todo 添加删除hosts的代码

        return Response::json(array(
            'code' => 0,
            'msg' => "删除成功",
        ));
    }
}
