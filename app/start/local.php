<?php

//
$local_routes_file = app_path().'/routes_local.php';
if (File::exists($local_routes_file)) {
    require $local_routes_file;
}
