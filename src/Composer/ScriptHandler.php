<?php

namespace Codyas\SkeletonBundle\Composer;

use Composer\Script\Event;
use Symfony\Component\Process\Process;

class ScriptHandler
{
    public static function installJsDependencies(Event $event)
    {
        $vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');
        $rootDir = dirname($vendorDir);

        $process = new Process(['php', 'bin/console', 'importmap:require', 'gridjs']);
        $process->setWorkingDirectory($rootDir);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }

        echo $process->getOutput();
    }
}
