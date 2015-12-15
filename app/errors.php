<?php
use Deploy\Exception\RequestException;
use Deploy\Exception\BaseException;
use Deploy\Exception\GithubException;
use Deploy\Exception\ResourceNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

App::error(function (BaseException $e) {
    Log::error(sprintf('ERRO; Client IP: %s Url: %s  %s ', Input::ip(), Input::url(), $e->getMessage()));
    if (Request::ajax()) {
        return Response::json(array('code' => 1, 'msg' => $e->getUserMessage()));
    }
    return Response::make($e->getUserMessage(), 500);
});

App::error(function (BaseException $e) {
    Log::error(sprintf('ERRO; Client IP: %s Url: %s  %s ', Input::ip(), Input::url(), $e->getMessage()));
    if (Request::ajax()) {
        return Response::json(array('code' => 1, 'msg' => $e->getUserMessage()));
    }
    return Response::make($e->getUserMessage(), 500);
});

App::error(function (NotFoundHttpException $e) {
    Log::error(sprintf('[HttpNotFound] Client IP: %s Url: %s  %s ', Input::ip(), Input::url(), $e->getMessage()));
    return Response::make('Page Not Found', 404);
});

App::error(function (ResourceNotFoundException $e) {
    Log::error(sprintf('Resource Not Found; Client IP: %s Url: %s  %s ', Input::ip(), Input::url(), $e->getMessage()));
    if (Request::ajax()) {
        return Response::json(array('code' => 1, 'msg' => $e->getUserMessage()));
    }
    return Response::make($e->getUserMessage(), 404);
});

App::error(function (Illuminate\Session\TokenMismatchException $e) {
    Log::error(sprintf('CSRF ERROR; Client IP: %s Url: %s  %s ', Input::ip(), Input::url(), $e->getMessage()));
    return Response::json(array('code' => 1, 'msg' => 'CSRF PROTECT'));
});

