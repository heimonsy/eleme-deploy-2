<?php

use Deploy\Account\User;

class UserController extends Controller
{
    public function register()
    {
        $input = Input::only('name', 'email');
        $validator = Validator::make(
            $input,
            array('name' => 'required', 'email' => 'required|email')
        );

        if ($validator->fails()) {
            return Response::json(array('res' => '1', 'info' => 'input error'));
        }
        $user = Sentry::loginUser();
        $user->notify_email = $input['email'];
        $user->name = $input['name'];
        $user->status = User::STATUS_WAITING;
        $user->save();

        // todo 添加刷新用户权限

        return Response::json(array('res' => 0, 'info' => route('wait')));
    }
}
