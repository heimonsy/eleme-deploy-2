<?php

use Deploy\Hosts\HostTypeCatalog;
use Deploy\Site\Site;

class SiteHostTypeCatalogController extends Controller
{
    public function index(Site $site)
    {
        return Response::json(array(
            'code' => 0,
            'data' => HostTypeCatalog::all()
        ));
    }
}
