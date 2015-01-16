<?php
namespace Deploy\Sentry;
use Deploy\Interfaces\AllObjectsInterface;

interface AccessProtectedInterface extends AllObjectsInterface
{
    public function accessDescription();

    public function accessAction();

    public static function allObjects();

    public static function accessActionList();
}
