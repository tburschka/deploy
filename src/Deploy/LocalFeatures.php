<?php

namespace Deploy;

class LocalFeatures
{

    const OS_LINUX   = 'Linux';
    const OS_WINDOWS = 'Windows';

    /**
     * @var array
     */
    private $configuration;

    /**
     * @param array $configuration
     */
    public function __construct($configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * Return name of the local OS
     *
     * @return string
     */
    public function getOS()
    {
        return php_uname('s');
    }

    /**
     * Return full path to the php binary
     *
     * @return string
     */
    public function getPHPBinary()
    {
        return PHP_BINARY;
    }

    /**
     * Return php version string
     *
     * @return string
     */
    public function getPHPVersion()
    {
        return PHP_VERSION;
    }

    protected function findExecutable($executable)
    {
        $output = array();
        if (self::OS_WINDOWS === $this->getOS()) {
            exec('where ' . $executable, $output);
        } else {
            exec('which ' . $executable, $output);
        }
        return $output;
    }
}
