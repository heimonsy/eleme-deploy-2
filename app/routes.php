<?php

Route::group(array('before' =>  'guest'), function () {
    Route::get('/login', array(
        'as' => 'login',
        'uses' => 'LoginController@login'
    ));

    Route::get('/github/signin', array(
        'as' => 'signin',
        'uses' => 'LoginController@signin'
    ));

    Route::get('/github/callback', array(
        'uses' => 'LoginController@callback'
    ));
});

Route::group(array('before' => array('auth')), function () {
    Route::get('/wait', array(
        'before' => 'normal',
        'as' => 'wait',
        'uses' => 'LoginController@wait'
    ));

    Route::get('/is-waiting', function () {
        $user = Sentry::loginUser();
        return Response::json(array('res' => 0, 'data' => $user->isWaiting()));
        //return Response::json(array('res' => 0, 'data' => false));
    });
});

Route::group(array('before' => array('auth', 'waiting')), function () {
    Route::get('/', array(
        'as' => 'dashboard',
        'uses' => 'SystemController@dashboard'
    ));

    Route::get('/logout', 'LoginController@logout');
});


