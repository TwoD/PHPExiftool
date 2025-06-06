<?php

/*
 * This file is part of PHPExifTool.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool;

use PHPExiftool\Exception\RuntimeException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

class Exiftool implements LoggerAwareInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * Execute a command and return the output
     *
     * @param  string     $command
     * @return string
     * @throws \Exception
     */
    public function executeCommand($command)
    {
        $command = self::getBinary() . ' ' . $command;
        $process = new Process($command);

        $this->logger->info(sprintf('Exiftool executes command %s', $process->getCommandLine()));

        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException(sprintf('Command %s failed : %s, exitcode %s', $command, $process->getErrorOutput(), $process->getExitCode()));
        }

        $output = $process->getOutput();

        unset($process);

        return $output;
    }

    /**
     *
     * @return string
     */
    protected static function getBinary()
    {
        static $binary = null;

        if ($binary) {
            return $binary;
        }

        $dev = __DIR__ . '/../../vendor/phpexiftool/exiftool/exiftool';
        $packaged = __DIR__ . '/../../../../phpexiftool/exiftool/exiftool';

        foreach (array($packaged, $dev) as $location) {
            if (defined('PHP_WINDOWS_VERSION_BUILD')) {
                $location .= '.exe';
            }

            if (is_executable($location)) {
                return $binary = realpath($location);
            }
        }

        throw new RuntimeException('Unable to get exiftool binary');
    }
}
