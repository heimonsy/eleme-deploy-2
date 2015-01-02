<?php
use Deploy\Exception\RequestException;
use Deploy\Exception\GithubException;

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
