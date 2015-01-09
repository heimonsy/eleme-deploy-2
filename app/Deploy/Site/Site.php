<?php
namespace Deploy\Site;

use Eloquent;

class Site extends Eloquent
{
    protected $table = 'sites';

    protected $guarded = array('id');
}
