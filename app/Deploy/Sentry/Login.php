<?php
namespace Deploy\Sentry;

use Session;
use Cookie;
use Deploy\Account\User;

class Login
{
    const SESSION_KEY = 'S_USER_ID';
    const COOKIE_KEY = 'C_USER_ID';

    protected $user;

    public function login(User $user)
    {
        $this->user = $user;

        $this->sessionUser($this->user->id);

        return $this->cookieUser($this->user->id);
    }

    public function sessionUser($id)
    {
        Session::set(static::SESSION_KEY, $id);
    }

    public function cookieUser($id)
    {
        $fid = User::fakeId($id);
        return Cookie::make(self::COOKIE_KEY, $fid, 60 * 24 * 30);
    }

    public function checkLogin()
    {
        if ($this->user || $this->checkFromSession() || $this->checkFromCookies()) {
            if(!$this->user->isDeleted()){
                $this->user->load('roles');
                return true;
            }
        }

        return false;
    }

    protected function checkFromCookies()
    {
        if ($fid = Cookie::get(static::COOKIE_KEY)) {
           $id = User::realId($fid);
           $this->user = User::find($id);

           return $this->user !== null;
        }
        return false;
    }

    protected function checkFromSession()
    {
        if ($id = Session::get(static::SESSION_KEY)) {
            $this->user = User::find($id);

            return $this->user !== null;
        }

        return false;
    }

    public function loginUser()
    {
        return $this->user;
    }

    public function logout()
    {
        Session::flush();
        return Cookie::forget(self::COOKIE_KEY);
    }
}
