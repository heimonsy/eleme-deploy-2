<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Deploy\Account\Role;

class InsertSystemRoles extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Role::create(array('name' => '管理员', 'description' => '默认的系统管理员', 'type' => Role::TYPE_SYSTEM, 'is_admin_role' => 1));
        Role::create(array('name' => '普通用户', 'description' => '新增用户的默认角色', 'type' => Role::TYPE_SYSTEM));
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Role::where('name', '=', '管理员')->delete();
        Role::where('name', '=', '普通用户')->delete();
    }
}
