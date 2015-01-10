<?php

use Deploy\Github\GithubClient;
use Deploy\Github\GithubAuth;
use Deploy\Account\User;
use Eleme\Deploy\Exception\RequestException;
use Deploy\Worker\Job;

class LoginController extends Controller
{
    public function login()
    {
        return Response::view('login');
    }

    public function signin()
    {
        $clientId = Config::get('github.client_id');
        $scope = Config::get('github.scope');
        $callbackUrl = Config::get('app.url') . '/github/callback';

        return Redirect::to(GithubAuth::authorizeUrl($clientId, $callbackUrl, $scope));
    }

    /**
     * github authorize callback
     */
    public function callback()
    {
        $code = Input::get('code');
        if (empty($code)) {
            throw new RequestException('code empty');
        }

        $clientId = Config::get('github.client_id');
        $clientSecret = Config::get('github.client_secret');
        $proxy = Config::get('github.proxy');

        $accessToken = GithubAuth::accessToken($clientId, $clientSecret, $code, $proxy);
        $client = new GithubClient($accessToken, $proxy);

        $userJson = $client->request('user');

        //获取orgs
        $orgs = $client->request('user/orgs');
        $flag = false;
        $organization = Config::get('github.organization');
        foreach ($orgs as $org) {
            if ($org['login'] === $organization) {
                $flag = true;
                break;
            }
        }

        if (!$flag) {
            // todo 添加operation日志
            Log::error("[haven't access right] github user {$userJson['login']} try to login.");
            return Response::make('用户无权限访问', 403);
        }

        $email = isset($userJson['email']) ? $userJson['email'] : '';

        $user = User::firstOrNew(array('login' => $userJson['login']));
        $user->email = $email;
        $user->token = $accessToken;
        if ($user->status === null) {
            $user->status = User::STATUS_REGISTER;
            $route = 'register';
        } elseif (!$user->isDeleted()) {
            $route = 'dashboard';
            //$user->status = User::STATUS_WAITING;
            //$route = 'wait';
            //Worker::push('Deploy\Worker\Jobs\UpdateUserTeams', Job::TYPE_USER, "Update User {$userJson['login']}",
                //array(
                    //'id' => 1,
                    //'status' => Deploy\Account\User::STATUS_WAITING
                //)
            //);

        } else {
            Log::error("[user has been deleted] github user {$userJson['login']} try to login");
            return Response::make('用户已被删除', 403);
        }
        $user->save();

        $cookie = Sentry::login($user);

        return Redirect::route($route)->withCookie($cookie);
    }

    public function wait()
    {
        return Response::view('wait');
    }

    public function logout()
    {
        $cookie = Sentry::logout();
        return Redirect::route('login')->withCookie($cookie);
    }

    public function register()
    {
        return Response::view('register', array(
            'data' => json_encode(array(
                'login' => 'heimonsy',
                'name' => '',
                'email' => 'heimonsy@gmail.com',
            )),
        ));
    }
}
