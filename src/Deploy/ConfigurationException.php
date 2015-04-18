<?php

namespace Deploy;

class ConfigurationException extends \RuntimeException
{

    /**
     * @return self
     */
    public static function fileNotFound()
    {
        return new self('The configuration file "deploy.json" is missing.');
    }

    /**
     * @param string
     * @return self
     */
    public static function parseError($errorMessage)
    {
        return new self($errorMessage);
    }
}