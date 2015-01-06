<?php
namespace Deploy\Worker\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Config;
use Log;
use Deploy\Worker\JobQueue;
use Deploy\Worker\Job;
use Deploy\Worker\Worker;

class WorkerCommand extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'worker:job';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'start worker';

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
        $handler = function ($signal) {
            if ($signal == SIGINT) {
                Log::info("RECV SIGINT");
            } elseif ($signal == SIGHUP) {
                Log::info("RECV SIGHUP");
            } elseif ($signal == SIGUSR1) {
                Log::info('RECV BUG');
                exit;
            }
        };

        pcntl_signal(SIGINT, $handler);
        pcntl_signal(SIGHUP, $handler);

        $pid = getmypid();

        $queueName = $this->argument('queue');
        $queue = new JobQueue($queueName);
        $jobId = $this->argument('jobid');
        $job = Job::find($jobId);
        $worker = new Worker($job, $queue, $pid);
        $worker->run();
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return array(
            array('queue', InputArgument::REQUIRED, 'Queue Name'),
            array('jobid', InputArgument::REQUIRED, 'Job Id.'),
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
