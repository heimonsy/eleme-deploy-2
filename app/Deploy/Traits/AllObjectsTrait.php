<?php
namespace Deploy\Traits;

trait AllObjectsTrait
{
    public static function allObjects()
    {
        static $all = null;

        if ($all === null) {
            $all = static::all();
        }

        return $all;
    }
}
