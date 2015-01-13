<?php
namespace Deploy\Sentry;

use Deploy\Account\User;

class Sentry
{
    protected $login;

    public function __construct(Login $login)
    {
        $this->login = $login;
    }

    public function __call($method, $parameters)
    {
        if (method_exists($this->login, $method)) {
            return call_user_func_array(array($this->login, $method), $parameters);
        }

        throw new \BadMethodCallException("Mothod {$method} is not supported by Sentry");
    }
}
