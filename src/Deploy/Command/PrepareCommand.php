<?php

namespace Deploy\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PrepareCommand extends AbstractRemoteCommand
{
    protected function configure()
    {
        parent::configure();
        $this->setName('prepare');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return integer
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $targets = $input->getArgument('targets');
        $output->setVerbosity(OutputInterface::VERBOSITY_NORMAL);
        foreach ($targets as $target) {
            $output->writeln('== target: ' . $target);
            $command = 'mkdir -p ' . $this->getDeployPath($target) . 'releases';
            $output->setVerbosity(OutputInterface::VERBOSITY_VERY_VERBOSE);
            $output->writeln('== command: ' . $command);
            $output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
            $response = $this->remoteExec($command, [$target]);
            $exitCode = $this->getLastExitCode($target);
            $output->setVerbosity(OutputInterface::VERBOSITY_NORMAL);
            if (0 === $exitCode) {
                $output->writeln('== OK');
            } else {
                $output->writeln('== ERROR');
                $output->writeln($response);
                return $exitCode;
            }
        }
        return 0;
    }
}