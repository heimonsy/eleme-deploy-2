<?php

Route::group(array('before' => 'guest'), function () {
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
        'before' => 'no.wait',
        'as' => 'wait',
        'uses' => 'LoginController@wait'
    ));

    Route::get('/register', array(
        'before' => 'no.register',
        'as' => 'register',
        'uses' => 'LoginController@register'
    ));

    Route::post('/user/register', array(
        'as' => 'post.register',
        'uses' => 'UserController@register',
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

// Manger Group
Route::group(
    array(
        'before' => array('auth', 'admin'),
        'prefix' => 'manager',
    ),
    function () {
        Route::get('role', 'ManagerController@role');
        Route::get('hosttypecatalogs', 'ManagerController@hosttypecatalogs');
        Route::get('sites', 'ManagerController@sites');
        Route::get('users', 'ManagerController@users');
    }
);

// Mangaer REST API

Route::model('role', 'Deploy\Account\Role', function () {
    throw new \Deploy\Exception\ResourceNotFoundException('角色不存在');
});

Route::Model('hosttype', 'Deploy\Hosts\HostType', function () {
    throw new \Deploy\Exception\ResourceNotFoundException('Host Type不存在');
});

Route::Model('hosttypecatalog', 'Deploy\Hosts\HostTypeCatalog', function () {
    throw new \Deploy\Exception\ResourceNotFoundException('发布环境不存在');
});

Route::Model('site', 'Deploy\Site\Site', function () {
    throw new \Deploy\Exception\ResourceNotFoundException('项目不存在');
});

Route::bind('user', function ($value, $route) {
    $user = User::where('id', $value)->normal()->first();
    if (!$user) {
        throw new \Deploy\Exception\ResourceNotFoundException('用户不存在');
    }
    return $user;
});

Route::group(
    array(
        'before' => array('auth', 'admin'),
        'prefix' => 'api',
    ),
    function () {
        Route::resource('role', 'RoleController', array(
            'only' => array('index', 'show', 'store', 'destroy', 'update')
        ));

        Route::resource('hosttype', 'HostTypeController', array(
            'only' => array('index', 'show', 'store', 'destroy', 'update')
        ));

        Route::resource('hosttypecatalog', 'HostTypeCatalogController', array(
            'only' => array('index', 'show', 'store', 'destroy', 'update')
        ));

        Route::resource('site', 'SiteController', array(
            'only' => array('index', 'show', 'store', 'destroy', 'update')
        ));

        Route::resource('user', 'UserController', array(
            'only' => array('index', 'destroy')
        ));
    }
);

Route::when('api/*', 'csrf', array('post'));
Route::when('api/*/*', 'csrf', array('put', 'delete'));

