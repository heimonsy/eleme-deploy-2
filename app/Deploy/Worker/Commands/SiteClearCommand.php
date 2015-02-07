<?php
namespace Deploy\Worker\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Config;
use Log;
use App;
use Deploy\Worker\JobQueue;
use Deploy\Worker\Job;
use Deploy\Worker\SampleTask;
use Deploy\Facade\Worker;

class SiteClearCommand extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'site:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'push a site clear job';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $job = Worker::createJob(
            'Deploy\Worker\Jobs\SiteClear',
            'Site Clear',
            array(),
            Job::TYPE_SYSTEM
        );

        Worker::push($job);
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return array(
        );
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array(
        );
    }

}
