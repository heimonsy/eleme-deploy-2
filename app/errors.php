<?php
use Deploy\Exception\RequestException;
use Deploy\Exception\GithubException;
use Deploy\Exception\ResourceNotFoundException;

App::error(function (ResourceNotFoundException $e) {
    Log::error(sprintf('Resource Not Found; Client IP: %s Url: %s  %s ', Input::ip(), Input::url(), $e->getMessage()));
    return Response::json(array('code' => 1, 'msg' => $e->getUserMessage()));
});

App::error(function (Illuminate\Session\TokenMismatchException $e) {
    Log::error(sprintf('CSRF ERROR; Client IP: %s Url: %s  %s ', Input::ip(), Input::url(), $e->getMessage()));
    return Response::json(array('code' => 1, 'msg' => 'CSRF PROTECT'));
});

if ($env !== 'local') {
    App::error(function (RequestException $e) {
        Log::error($e);

        return Response::make($e->getUserMessage(), 400);
    });

    App::error(function (GithubException $e) {
        Log::error($e);

        return Response::make($e->getUserMessage(), 500);
    });
}
