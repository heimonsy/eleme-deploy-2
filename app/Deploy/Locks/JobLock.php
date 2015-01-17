<?php
namespace Deploy\Locks;


class JobLock
{
    const KEY_PREFIX = 'DEPLOY:LOCK:JOB:';

    public static function buildRepo($siteId)
    {
        return self::KEY_PREFIX . 'BUILD:REPO:' . $siteId;
    }
}
