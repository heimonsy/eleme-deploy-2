<?php
namespace Deploy\Worker;


interface JobInterface
{
    public function descriptYourself($message);
    public function fire(Worker $worker, $message);
}
