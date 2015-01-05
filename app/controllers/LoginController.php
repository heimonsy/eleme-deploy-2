<?php

use Deploy\Github\GithubClient;
use Deploy\Github\GithubAuth;
use Deploy\Account\User;
use Eleme\Deploy\Exception\RequestException;

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
        $email = isset($userJson['email']) ? $userJson['email'] : '';

        $user = User::firstOrNew(array('login' => $userJson['login']));
        $user->email = $email;
        $user->token = $accessToken;
        if ($user->status === null) {
            $user->status = User::STATUS_REGISTER;
            $route = 'register';
        } elseif (!$user->isDeleted()) {
            $user->status = User::STATUS_WAITING;
            $route = 'wait';
            // todo 添加刷新用户权限
        } else {
            App::abort(403, '用户已被删除');
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
