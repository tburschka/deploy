<?php

namespace Deploy;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

class Deploy extends Application
{

    /**
     * @var array
     */
    protected $configuration;

    /**
     * Constructor.
     *
     * @param string $name    The name of the application
     * @param string $version The version of the application
     *
     * @api
     */
    public function __construct($name = 'UNKNOWN', $version = 'UNKNOWN')
    {
        parent::__construct($name, $version);
        $this->getDefinition()
            ->addOption(new InputOption(
                '--configuration',
                '-c',
                InputOption::VALUE_REQUIRED,
                'Set the configuration file',
                getcwd() . '/' . Configuration::FILENAME
            ))
        ;
    }

    /**
     * Gets the default commands that should always be available.
     *
     * @return array An array of default Command instances
     */
    protected function getDefaultCommands()
    {
        $defaultCommands = parent::getDefaultCommands();
        $defaultCommands[] = new Command\CheckLocalFeaturesCommand();
        $defaultCommands[] = new Command\CheckRemoteFeaturesCommand();
        $defaultCommands[] = new Command\PrepareCommand();
        return $defaultCommands;
    }

    /**
     * @param string $configuration
     * @return string
     */
    public function getConfigurationFile($configuration)
    {
        $configuration = realpath($configuration);
        if (is_dir($configuration)) {
            $configuration = $configuration . '/' . Configuration::FILENAME;
        }
        if (is_file($configuration)) {
            return $configuration;
        }
        return '';
    }

    /**
     * @param InputInterface $input
     * @return array
     */
    public function getConfiguration(InputInterface $input)
    {
        if (null === $this->configuration) {
            $configurationFile = $this->getConfigurationFile($input->getOption('configuration'));
            if (!$configurationFile) {
                throw ConfigurationException::fileNotFound($input->getOption('configuration'));
            }
            $configs = json_decode(file_get_contents($configurationFile), true);
            if (JSON_ERROR_NONE !== json_last_error()) {
                throw ConfigurationException::parseError(json_last_error_msg());
            } elseif (is_null($configs)) {
                throw ConfigurationException::parseError('invalid json');
            }
            $processor = new Processor();
            $configTree = new Configuration();
            $this->configuration = $processor->process(
                $configTree->getConfigTreeBuilder()->buildTree(),
                ['deploy' => $configs]
            );
        }
        return $this->configuration;
    }
}