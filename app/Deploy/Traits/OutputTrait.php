<?php
namespace Deploy\Traits;

use Symfony\Component\Process\Process;
use Deploy\Interfaces\OutputInterface;

trait OutputTrait
{
    abstract public function getId();

    public function getKey()
    {
        return 'DEPLOY:L:OUTPUT:' . $this->getId();
    }

    public function clear()
    {
        return $this->redis()->del($this->getKey());
    }

    public function getOutput()
    {
        return $this->redis()->lrange($this->getKey(), 0, -1);
    }

    public function outputCallback()
    {
        // $this 在匿名函数中使用，需要php5.4
        return function ($type, $buffer) {
            static $errLine = '';
            static $outLine = '';

            $len = strlen($buffer);
            if (Process::ERR === $type) {
                $errLine .= $buffer;
                if ($buffer[$len-1] == "\n") {
                    $errLine = preg_replace('/"Enter passphrase" \{ send ".+/', '--------', $errLine);
                    $this->line(OutputInterface::ERR . $this->lastLine($errLine));
                    $errLine = '';
                }
            } else {
                $outLine .= $buffer;
                if ($buffer[$len-1] == "\n") {
                    $this->line(OutputInterface::OUT . $this->lastLine($outLine));
                    $outLine = '';
                }
            }
        };
    }

    protected function lastLine($out)
    {
        $arr = explode("\r", $out);
        $len = count($arr);
        if ($len == 1 ) {
            return $out;
        }
        return $arr[$len-2] . $arr[$len-1];
    }

    public function commandLine($line)
    {
        $this->line(OutputInterface::CMD . $line);
    }

    public function outputLine($line)
    {
        $this->line(OutputInterface::OUT . $line);
    }

    protected function line($line)
    {
        $this->redis()->rpush($this->getKey(), $line);
    }

    protected function redis()
    {
        static $redis;
        if ($redis == null) {
            $redis = app('redis')->connection();
        }

        return $redis;
    }
}
