<?php
namespace Deploy\Sentry;
use Deploy\Interfaces\AllObjectsInterface;

interface ManageProtectedInterface extends AllObjectsInterface
{
    public function manageDescription();

    public function manageAction();

    public static function allObjects();

    public function manageActionList();
}
