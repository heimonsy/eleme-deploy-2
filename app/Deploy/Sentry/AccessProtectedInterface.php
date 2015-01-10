<?php
namespace Deploy\Sentry;

interface AccessProtectedInterface
{
    public function accessDescription();

    public function accessAction();

    public static function allObjects();

    public static function accessActionList();
}
