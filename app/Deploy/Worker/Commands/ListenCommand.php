<?php
namespace Deploy\Worker\Commands;

use Config;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Deploy\Worker\JobQueue;
use Deploy\Worker\Supervisor;

class ListenCommand extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'worker:listen';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'start worker listen';

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
        $queueName = $this->argument('queue');
        $queueName = $queueName ?: 'main';
        $queueName = Config::get('worker.queues.' . $queueName);

        $queue = new JobQueue($queueName);
        $taskQueue = new JobQueue($queueName . ':task');

        $supervisor = new Supervisor($queue, $taskQueue);

        $handler = function ($signal) use ($supervisor) {
            if ($signal == SIGINT) {
                echo "Aborting...\n";
                $supervisor->offDuty();
            } elseif ($signal == SIGHUP) {
                Log::info("RECV SIGHUP");
            }
        };
        pcntl_signal(SIGINT, $handler);
        pcntl_signal(SIGHUP, $handler);

        $supervisor->listen(getmypid());
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return array(
            array('queue', InputArgument::OPTIONAL, 'listen queue'),
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
