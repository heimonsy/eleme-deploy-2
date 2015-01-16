<?php
namespace Deploy\Sentry;

trait ManageProtectedTrait
{
    abstract public function identify();
    abstract public function manageDescription();

    public function manageAction()
    {
        return 'manage_' . $this->identify();
    }

    public static function manageActionList()
    {
        $all = static::allObjects();
        $res = array();
        foreach ($all as $m) {
            $res[] = array(
                'action' => $m->manageAction(),
                'description' => $m->manageDescription(),
            );
        }
        return $res;
    }
}
