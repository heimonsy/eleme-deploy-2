<?php

use Deploy\Account\User;
use Deploy\Worker\Job;
use Deploy\Account\Role;

class UserController extends Controller
{
    public function index()
    {
        return Response::json(array(
            'code' => 0,
            'data' => User::normal()->with(array('roles' => function ($query) {
                          $query->select('roles.id', 'name', 'is_admin_role')->orderBy('roles.id');
                      }))->get()
        ));
    }

    public function destroy(User $user)
    {
        $user->STATUS = User::STATUS_DELETE;
        $user->save();

        return Response::json(array(
            'code' => 0,
            'msg' => '删除成功',
        ));
    }

    public function register()
    {
        Input::merge(array_map('trim', Input::only('name', 'email')));
        $validator = Validator::make(
            Input::only('name', 'email'),
            array('name' => 'required', 'email' => 'required|email|unique:users,email'),
            array(
                'name.required' => '名字不能为空',
                'email.required' => '邮箱不能为空',
                'email.email' => '邮箱格式错误',
                'email.unique' => '邮箱已存在',
            )
        );

        if ($validator->fails()) {
            return Response::json(array(
                'code' => 1,
                'msg' => $validator->messages()->all(":message\n"),
            ));
        }
        $user = Sentry::loginUser();
        $user->email = Input::get('email');
        $user->notify_email = Input::get('email');
        $user->name = Input::get('name');
        //$user->status = User::STATUS_WAITING;
        $user->status = User::STATUS_NORMAL;
        $user->save();

        if ($user->id == 1) {
            $role = Role::where(array('type' => Role::TYPE_SYSTEM, 'is_admin_role' => 1))->first();
            $user->roles()->attach($role->id);
        } else {
            //$role = Role::where(array('type' => Role::TYPE_SYSTEM, 'is_admin_role' => 0))->first();
            //$user->roles()->attach($role->id);
        }

        //Worker::push('Deploy\Worker\Jobs\UpdateUserTeams', Job::TYPE_USER, "Update User {$user->login}",
            //array(
                //'id' => 1,
                //'status' => User::STATUS_WAITING
            //)
        //);

        return Response::json(array('res' => 0, 'info' => route('dashboard')));
    }
}
