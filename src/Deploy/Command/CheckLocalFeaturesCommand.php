<?php

namespace Deploy\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckLocalFeaturesCommand extends AbstractCommand
{

    protected function configure()
    {
        $this->setName('check:local-features');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('-=| local features |=-');
        $output->writeln('OS: ' . $this->getLocalFeatures()->getOS());
        $output->writeln('PHP binary: ' . $this->getLocalFeatures()->getPHPBinary());
        $output->writeln('PHP version: ' . $this->getLocalFeatures()->getPHPVersion());
        $output->writeln('done!');
    }
}