<?php

use Deploy\Account\User;
use Deploy\Worker\Job;

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

        Worker::push('Deploy\Worker\Jobs\UpdateUserTeams', Job::TYPE_USER, "Update User {$user->login}",
            array(
                'id' => 1,
                'status' => User::STATUS_WAITING
            )
        );

        return Response::json(array('res' => 0, 'info' => route('wait')));
    }
}
