<?php

class SystemController extends Controller
{
    public function dashboard()
    {
        return Response::view('dashboard', array('hehe' => 'test 123'));
    }
}
