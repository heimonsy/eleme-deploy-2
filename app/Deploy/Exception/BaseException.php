<?php
namespace Deploy\Exception;

use Exception;

class BaseException extends Exception
{
    protected $userMessage;

    public function __construct($userMessage, $message = null, $code = 0, Exception $previous = null)
    {
        $this->userMessage = $userMessage;

        if ($message === null) {
            $message = $this->userMessage;
        }

        parent::__construct($message, $code, $previous);
    }

    public function getUserMessage()
    {
        return $this->userMessage;
    }
}
