<?php
namespace Deploy\Worker;

use Symfony\Component\Process\Process;
use SSHProcess\SSHProcess;
use SSHProcess\RsyncProcess;
use Deploy\Worker\GitProcess;
use Deploy\Interfaces\OutputInterface;

abstract class Task
{
    protected $job;
    protected $message;

    public function __construct(OutputInterface $job)
    {
        $this->job = $job;
        $this->message = $job->message;
    }

    abstract public function fire($worker);

    public function processCommands($CMDS, $remoteHostName = NULL, $address = null, $username = null, $identifyfile = null, $passphrase = null, $port = 22)
    {
        $error = false;
        foreach ($CMDS as $command) {
            if ($remoteHostName === NULL) {
                $process = $this->process($command, null, false);
            } else {
                $process = $this->sshProcess($remoteHostName, $address, $username, $command, $identifyfile, $passphrase, null, $port, false);
            }
            $error = $error || !$process->isSuccessful();
        }
        return $error;
    }

    public function process($command, $cwd = null, $must = true)
    {
        $process = new Process($command, $cwd);

        return $this->run($process, $command, $must);
    }

    public function gitProcess($command, $cwd = null, $identifyfile = null, $passphrase = null, $must = true)
    {
        $process = new GitProcess($command, $cwd, $identifyfile, $passphrase, 600);

        return $this->run($process, $command, $must);
    }

    public function sshProcess($host, $address, $username, $command, $identifyfile, $passphrase, $cwd = null, $port = 22, $must = true)
    {
        $process = new SSHProcess($host, $address, $username, $command, $identifyfile, $passphrase, null, $port, 600);

        return $this->run($process, $command, $must);
    }

    public function rsyncProcess($hostname, $address, $username, $exclude, $localDir, $remoteDir, $forceDelete, $identityfile = null, $passphrase = null, $cwd = null, $port = 22, $must = true)
    {
        $process = new RsyncProcess($hostname, $address, $username, $exclude, $localDir, $remoteDir, $forceDelete, $identityfile, $passphrase, $cwd, $port, 600);

        return $this->run($process, 'RSYNC', $must);
    }

    public function run(Process $process, $originCommand, $must = true)
    {
        $this->job->commandLine($originCommand);

        $start = microtime(true);
        if ($must) {
            $process->setTimeout(600)->mustRun($this->job->outputCallback());
        } else {
            $process->setTimeout(600)->run($this->job->outputCallback());
        }

        $this->job->outputLine('-- ' . (microtime(true) - $start) . ' seconds --');
        return $process;
    }
}
