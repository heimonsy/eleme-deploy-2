<?php
namespace Deploy\Sentry;

interface ControllerInterface
{
    public function control($action);

    public function isAdmin();
}
