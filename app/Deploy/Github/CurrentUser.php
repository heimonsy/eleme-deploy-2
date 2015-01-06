<?php
namespace Deploy\Github;

use Github\Api\CurrentUser as Base;

class CurrentUser extends Base
{
    /**
     * @link http://developer.github.com/v3/orgs/#list-user-organizations
     *
     * @return array
     */
    public function teams()
    {
        return $this->get('user/teams');
    }
}
