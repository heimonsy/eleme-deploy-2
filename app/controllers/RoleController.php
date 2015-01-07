<?php

use Deploy\Account\Role;


class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::all();
        return Response::json(array(
            'code' => 0,
            'data' => $roles->toArray()
        ));
    }

    public function show($role)
    {
        return Response::json(array('code' => 0, 'data' => $role));
    }

    public function store()
    {
        Input::merge(array_map('trim', Input::only('roleName')));

        $validator = Validator::make(
            Input::only('roleName', 'roleType'),
            array('roleName' => 'required|unique:roles,name', 'roleType' => 'in:0, 1'),
            array(
                'roleName.required' => '角色名不能为空',
                'roleName.unique' => '角色名已存在',
                'roleType.required' => '角色类型必须是"普通角色"或"管理角色"',
            )
        );
        if ($validator->fails()) {
            $messages = $validator->messages();
            return Response::json(array(
                'code' => 1,
                'msg' => $messages->first()
            ));
        }

        $role = new Role;
        $role->name = Input::get('roleName');
        $role->is_admin_role = Input::get('roleType');
        $role->save();

        return Response::json(array(
            'code' => 0,
            'msg' => '创建成功',
        ));
    }

    public function update(Role $role)
    {
        Input::merge(array_map('trim', Input::only('roleName')));

        $validator = Validator::make(
            Input::only('roleName', 'roleType'),
            array('roleName' => 'required|unique:roles,name,' . $role->id, 'roleType' => 'in:0, 1'),
            array(
                'roleName.required' => '角色名不能为空',
                'roleName.unique' => '角色名已存在',
                'roleType.required' => '角色类型必须是"普通角色"或"管理角色"',
            )
        );
        if ($validator->fails()) {
            $messages = $validator->messages();
            return Response::json(array(
                'code' => 1,
                'msg' => $messages->first()
            ));
        }

        $role->name = Input::get('roleName');
        $role->is_admin_role = Input::get('roleType');
        $role->save();

        return Response::json(array('code' => 0, 'msg' => '保存成功'));
    }

    public function destroy(Role $role)
    {

        if ($role->type === Role::TYPE_SYSTEM) {
            return Response::json(array('code' => 0, 'msg' => '无法删除系统创建的角色'));
        } else {
            $role->delete();
            return Response::json(array('code' => 0, 'msg' => '删除成功'));
        }
    }
}
