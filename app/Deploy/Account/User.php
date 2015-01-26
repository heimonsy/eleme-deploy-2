<?php
namespace Deploy\Account;

use Eloquent;
use Crypt;
use Deploy\Sentry\ControllerInterface;

class User extends Eloquent implements ControllerInterface
{
    const STATUS_DELETE = 0;
    const STATUS_NORMAL = 1;
    // 等待录入通知邮箱和姓名
    const STATUS_REGISTER = 9;
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

    public function roles()
    {
        return $this->belongsToMany('Deploy\Account\Role', 'role_user', 'user_id', 'role_id');
    }

    public function teams()
    {
        return $this->belongsToMany('Deploy\Account\Team', 'team_user', 'user_id', 'team_id');
    }

    public function repos()
    {
         return $this->hasManyThrough('Deploy\Account\Repo', 'Deploy\Account\Team', 'user_id', 'team_id');
    }

    public function scopeNormal($query)
    {
        return $query->where('status', '=', self::STATUS_NORMAL);
    }

    public function isWaiting()
    {
        return $this->status == self::STATUS_WAITING;
    }

    public function isDeleted()
    {
        return $this->status == self::STATUS_DELETE;
    }

    public function isRegister()
    {
        return $this->status == self::STATUS_REGISTER;
    }

    public function isNormal()
    {
        return $this->status == self::STATUS_NORMAL;
    }

    public function isAdmin()
    {
        static $isAdmin = null;

        if ($isAdmin === null) {
            $isAdmin = false;
            foreach ($this->roles as $role) {
                if ($role->is_admin_role == 1) {
                    $isAdmin = true;
                    break;
                }
            }
        }
        return $isAdmin;
    }

    protected static $permissions = array();

    public function permissions()
    {
        if (!isset(self::$permissions[$this->id])) {
            self::$permissions[$this->id] = array();
            foreach ($this->roles as $role) {
                self::$permissions[$this->id] = array_merge(self::$permissions[$this->id], $role->permissions()->lists('name'));
            }
            self::$permissions[$this->id] = array_unique(self::$permissions[$this->id]);
        }

        return self::$permissions[$this->id];
    }

    public function control($action)
    {
        return in_array($action, $this->permissions()) || $this->isAdmin();
    }

    public function toArray()
    {
        return array_merge(parent::toArray(), array('permissions' => $this->permissions()));
    }

    public function watchs()
    {
        return $this->belongsToMany('Deploy\Site\Site', 'watchs', 'user_id', 'site_id');
    }
}
