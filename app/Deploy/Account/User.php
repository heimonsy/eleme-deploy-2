<?php
namespace Deploy\Account;

use Eloquent;
use Crypt;

class User extends Eloquent
{
    const STATUS_DELETE = 0;
    const STATUS_NORMAL = 1;
    // 等待拉取数据
    const STATUS_WAITING = 10;
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';

    protected $guarded = array('id');

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = array('token');

    public static function fakeId($realId)
    {
        $offset = \Config::get('user.offset');
        return $realId + $offset;
    }

    public static function realId($fakeId)
    {
        $offset = \Config::get('user.offset');
        return $fakeId - $offset;
    }

    public function isWaiting()
    {
        return $this->status == self::STATUS_WAITING;
    }
}
