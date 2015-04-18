<?php

namespace Deploy\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckRemoteFeaturesCommand extends AbstractRemoteCommand
{
    protected function configure()
    {
        parent::configure();
        $this->setName('check:remote-features');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $targets = $input->getArgument('targets');
        $output->writeln('done!');
    }
}