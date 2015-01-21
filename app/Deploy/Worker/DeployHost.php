<?php
namespace Deploy\Worker;

use Eloquent;
use Deploy\Interfaces\OutputInterface;
use Deploy\Traits\OutputTrait;

class DeployHost extends Eloquent implements OutputInterface
{
    const STATUS_WAITING = 'Waiting';
    const STATUS_DEPLOYING = 'Deploying';
    const STATUS_ERROR = 'Error';
    const STATUS_FINISH = 'Finish';

    use OutputTrait;

    public function getId()
    {
        return  'DEPLOY:HOST:' . $this->id;
    }

    public function delete()
    {
        $this->clear();
        parent::delete();
    }
}
