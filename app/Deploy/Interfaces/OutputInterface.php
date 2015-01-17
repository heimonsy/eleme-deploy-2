<?php
namespace Deploy\Interfaces;


interface OutputInterface
{
    const CMD = 'cmd ';
    const ERR = 'err ';
    const OUT = 'out ';

    public function outputCallback();

    public function commandLine($line);

    public function outputLine($line);

    public function getOutput();
}
