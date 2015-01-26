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
use Deploy\Worker\Worker;
use Deploy\Worker\SampleTask;

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
       $pid = getmypid();

        $queueName = $this->argument('queue');
        $queue = new JobQueue($queueName);
        $id = $this->argument('id');
        $type = $this->argument('type');
        if ($type == 'sampletask') {
            $job = SampleTask::find($id);
        } else {
            $job = Job::find($id);
        }
        $worker = new Worker($job, $queue, $pid);

        $handler = function ($signal) use ($worker) {
            if ($signal == SIGINT) {
                Log::info("RECV SIGINT");
            } elseif ($signal == SIGHUP) {
                Log::info("RECV SIGHUP");
            } elseif ($signal == SIGUSR1) {
                Log::info('RECV USER KILL');
                $worker->fireKillCallback();
                exit;
            }
        };

        pcntl_signal(SIGINT, $handler);
        pcntl_signal(SIGHUP, $handler);

        App::fatal(function ($exception) use ($worker) {
            $worker->fireFatalErrorCallback($exception);
            die();
        });

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
            array('type', InputArgument::REQUIRED, 'WorkType'),
            array('queue', InputArgument::REQUIRED, 'Queue Name'),
            array('id', InputArgument::REQUIRED, 'Job Or Sample Task Id.'),
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
