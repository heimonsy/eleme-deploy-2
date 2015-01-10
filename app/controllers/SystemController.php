<?php

class SystemController extends BaseController
{
    public function dashboard()
    {
        return Response::view('dashboard', array('hehe' => 'test 123'));
    }
}
