<?php

namespace Deploy\Command;

use Deploy\LocalFeatures;
use Deploy\RemoteFeatures;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractCommand extends Command
{

    /**
     * @var LocalFeatures
     */
    protected $localFeatures;

    /**
     * @var RemoteFeatures[]
     */
    protected $remoteFeaturesList = [];

    /**
     * @var InputInterface
     */
    protected $_input;

    /**
     * @param string $target
     * @return null|array
     */
    protected function getConfiguration($target = null)
    {
        /* @var \Deploy\Deploy $application */
        $application = $this->getApplication();
        $configuration = $application->getConfiguration($this->_input);
        if ($target) {
            if (!array_key_exists('targets', $configuration) || !array_key_exists($target, $configuration['targets'])) {
                throw new \RuntimeException(sprintf('Invalid target: "%s"', $target));
            }
            return $configuration['targets'][$target];
        }
        return $configuration;
    }

    /**
     * @inheritdoc
     *
     * $input is required for configuration, so it has to stored in command
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     */
    protected function initialize($input, $output)
    {
        $this->_input = $input;
    }

    /**
     * @return LocalFeatures
     */
    protected function getLocalFeatures()
    {
        if (null === $this->localFeatures) {
            $this->localFeatures = new LocalFeatures($this->getConfiguration());
        }
        return $this->localFeatures;
    }

    /**
     * @param string $identifier
     * @return RemoteFeatures
     */
    protected function getRemoteFeatures($identifier)
    {
        if (!array_key_exists($identifier, $this->remoteFeaturesList)) {
            $this->remoteFeaturesList[$identifier] = new RemoteFeatures($this->getConfiguration());
        }
        return $this->remoteFeaturesList[$identifier];
    }
}
