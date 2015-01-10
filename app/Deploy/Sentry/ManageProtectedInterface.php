<?php
namespace Deploy\Sentry;

interface ManageProtectedInterface
{
    public function manageDescription();

    public function manageAction();

    public static function allObjects();

    public function manageActionList();
}
