<?php

class SystemController extends BaseController
{
    public function dashboard()
    {
        $dashboard = addcslashes(file_get_contents(base_path() . '/dashboard.md'), "\n\"");
        return Response::view('dashboard', array('dashboard' => $dashboard));
    }
}
