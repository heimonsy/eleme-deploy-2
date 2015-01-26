<?php


Route::post('/payload/site/{site}', 'SitePullRequestBuildController@store');

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
        'before' => array('auth', 'waiting', 'admin'),
        'prefix' => 'manager',
    ),
    function () {
        Route::get('role', 'ManagerController@role');
        Route::get('hosttypecatalogs', 'ManagerController@hosttypecatalogs');
        Route::get('sites', 'ManagerController@sites');
        Route::get('users', 'ManagerController@users');
        Route::get('system/configure', 'ManagerController@configure');
    }
);

// Mangaer REST API

Route::model('role', 'Deploy\Account\Role', function () {
    throw new \Deploy\Exception\ResourceNotFoundException('角色不存在');
});

Route::Model('hosttype', 'Deploy\Hosts\HostType', function () {
    throw new \Deploy\Exception\ResourceNotFoundException('Host Type不存在');
});

Route::model('hosttypecatalog', 'Deploy\Hosts\HostTypeCatalog', function () {
    throw new \Deploy\Exception\ResourceNotFoundException('发布环境不存在');
});

Route::model('host', 'Deploy\Hosts\Host', function () {
    throw new \Deploy\Exception\ResourceNotFoundException('主机不存在');
});

Route::Model('site', 'Deploy\Site\Site', function () {
    throw new \Deploy\Exception\ResourceNotFoundException('项目不存在');
});

Route::Model('job', 'Deploy\Worker\Job', function () {
    throw new \Deploy\Exception\ResourceNotFoundException('Job不存在');
});

Route::Model('prbuild', 'Deploy\Site\PullRequestBuild', function () {
    throw new \Deploy\Exception\ResourceNotFoundException('Job不存在');
});

Route::Model('commit', 'Deploy\Site\Commit', function () {
    throw new \Deploy\Exception\ResourceNotFoundException('Commit不存在');
});

Route::Model('deployhost', 'Deploy\Worker\DeployHost', function () {
    throw new \Deploy\Exception\ResourceNotFoundException('Deploy Host不存在');
});



Route::bind('user', function ($value, $route) {
    $user = Deploy\Account\User::where('id', $value)->normal()->first();
    if (!$user) {
        throw new \Deploy\Exception\ResourceNotFoundException('用户不存在');
    }

    return $user;
});

// site auth
Route::group(
    array(
        'before' => array('auth', 'waiting'),
    ),
    function () {
        Route::get('/site/{site}', array(
            'before' => 'site.control',
            'uses' => 'ManagerController@site'
        ));
    }
);

// no admin auth api
Route::group(
    array(
        'before' => array('auth', 'site.control'),
    ),
    function () {
        Route::get('/api/site/{site}/configure', 'ApiController@showSiteConfig');
        Route::get('/api/site/{site}/deploy_configure', 'ApiController@showDeployConfig');
        Route::get('/api/system/config', 'ApiController@showSystemConfig');

        Route::get('/api/site/{site}/typenv', 'ApiController@siteTypeAndEnv');

        Route::post('/api/site/{site}/deploy', array(
            'before' => array('csrf'),
            'uses' => 'ApiController@siteDeploy'
        ));

        Route::get('/api/site/{site}/deploy', 'ApiController@indexDeploy');

        Route::put('/api/site/{site}/prrebuild', array(
            'before' => array('csrf'),
            'uses' => 'ApiController@prRebuild'
        ));

        Route::put('/api/site/{site}/configure', array(
            'before' => array('csrf'),
            'uses' =>  'ApiController@updateSiteConfig'
        ));

        Route::put('/api/site/{site}/deploy_configure', array(
            'before' => array('csrf'),
            'uses' =>  'ApiController@updateDeployConfig'
        ));

        Route::post('/api/site/{site}/watch', array(
            'before' => array('csrf'),
            'uses' => 'SiteController@watch'
        ));

        Route::post('/api/site/{site}/unwatch', array(
            'before' => array('csrf'),
            'uses' => 'SiteController@unwatch'
        ));
    }
);

/**
 * 需要site access control 权限的api
 */
Route::group(
    array(
        'before' => array('auth', 'site.control'),
        'prefix' => 'api'
    ),
    function () {
        Route::resource('site.hosttype', 'SiteHostTypeController', array(
            'only' => array('index', 'show', 'store', 'destroy', 'update')
        ));

        Route::resource('site.host', 'SiteHostController', array(
            'only' => array('index', 'show', 'store', 'destroy', 'update')
        ));

        Route::resource('site.build', 'SiteBuildController', array(
            'only' => array('index', 'show', 'store')
        ));

        Route::resource('site.hosttypecatalog', 'SiteHostTypeCatalogController', array(
            'only' => array('index')
        ));

        Route::resource('site.job', 'SiteJobController', array(
            'only' => array('show')
        ));

        Route::resource('site.deployhost', 'DeployHostController', array(
            'only' => array('show')
        ));

        Route::resource('site.prbuild', 'SitePullRequestBuildController', array(
            'only' => array('index')
        ));

        Route::resource('site.commit', 'SiteCommitController', array(
            'only' => array('index')
        ));
    }
);

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
    }
);

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

        Route::get('role/{role}/permission', 'ApiController@indexRolePermission');
        Route::post('role/{role}/permission', 'ApiController@storeRolePermission');

        Route::post('user/{user}/role', 'ApiController@storeUserRole');
        Route::delete('user/{user}/role/{role}', 'ApiController@destroyUserRole');
    }
);

Route::when('api/*', 'csrf', array('post'));
Route::when('api/*/*', 'csrf', array('put', 'delete', 'post'));
Route::when('api/*/*/*/*', 'csrf', array('put', 'delete', 'post'));

