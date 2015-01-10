<?php
namespace Deploy\Traits;

trait AllObjectsTrait
{
    abstract public static function all($fields = Array());

    public static function allObjects()
    {
        static $all = null;

        if ($all === null) {
            $all = static::all();
        }

        return $all;
    }
}
