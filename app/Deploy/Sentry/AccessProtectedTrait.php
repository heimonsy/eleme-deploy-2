<?php
namespace Deploy\Sentry;

trait AccessProtectedTrait
{
    abstract public function identify();
    abstract public function accessDescription();

    public function accessAction()
    {
        return 'access_' . $this->identify();
    }

    public static function accessActionList()
    {
        $all = static::allObjects();
        $res = array();
        foreach ($all as $m) {
            $res[] = array(
                'action' => $m->accessAction(),
                'description' => $m->accessDescription(),
            );
        }
        return $res;
    }
}
