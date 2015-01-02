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
        $user->name = $user->name ?: $userJson['login'];
        $user->email = $email;
        $user->token = $accessToken;
        $user->status = User::STATUS_NORMAL;
        $user->save();

        $cookie = Sentry::login($user);

        return Redirect::to('/wait')->withCookie($cookie);
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
}
