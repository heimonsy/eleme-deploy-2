<?php

use Deploy\Account\Role;
use Deploy\Sentry\Permission;
use Deploy\Site\Site;
use Deploy\Hosts\HostTypeCatalog;


class ApiController extends Controller
{
    public function indexRolePermission(Role $role)
    {
        $permissions = $role->permissions()->lists('name');
        $addIsControlled = function (&$list) use ($permissions) {
            foreach ($list as $key => $value) {
                if (in_array($list[$key]['action'], $permissions)) {
                    $list[$key]['is_controlled'] = 1;
                } else {
                    $list[$key]['is_controlled'] = 0;
                }
            }
        };

        $siteAccess = Site::accessActionList();
        $siteManage = Site::manageActionList();
        $hostTypeCatalogAccess =  HostTypeCatalog::accessActionList();

        $addIsControlled($siteAccess);
        $addIsControlled($siteManage);
        $addIsControlled($hostTypeCatalogAccess);

        return Response::json(array(
            'code' => 0,
            'data' => array(
                'name' => $role->name,
                'id' => $role->id,
                'permissions' => array(
                    array(
                        'description' => '站点管理权限',
                        'list' => $siteManage,
                    ),
                    array(
                        'description' => '站点发布权限',
                        'list' => $siteAccess,
                    ),
                    array(
                        'description' => '环境发布权限',
                        'list' => $hostTypeCatalogAccess
                    )
                )
            )
        ));
    }

    public function storeRolePermission(Role $role)
    {
        $list = Input::get('permissions');
        if (!is_array($list)) {
            return Response::json(array('code' => 1, 'msg' => 'permissions must be an array'));
        }
        DB::transaction(function () use($list, $role) {
            $role->permissions()->delete();
            $permissions = [];
            foreach ($list as $value) {
                $permissions[] = $role->permissions()->create(array('name' => $value));
            }
            $role->permissions()->saveMany($permissions);
        });

        return Response::json(array('code' => 0, 'msg' => '权限修改成功'));
    }
}
