<?php

namespace Deploy\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SyncCommand extends AbstractRemoteCommand
{
    protected function configure()
    {
        parent::configure();
        $this->setName('sync');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return integer
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $targets = $input->getArgument('targets');
        foreach ($targets as $target) {
            $configuration = $this->getConfiguration($target);
            $file = $configuration['deploy_file'];
            $scp = $this->getTargetSCP($target);
            $output->writeln('sync');
        }
        return 0;
    }
}