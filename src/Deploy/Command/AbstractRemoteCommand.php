<?php

namespace Deploy\Command;

use phpseclibBridge\Bridge;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractRemoteCommand extends AbstractCommand
{

    /**
     * @var \Net_SSH2[]
     */
    protected static $targetSSH = array();

    /**
     * @var \Net_SCP[]
     */
    protected static $targetSCP = array();

    /**
     * @var array
     */
    protected static $exitCodes = [];

    protected function configure()
    {
        $this->addArgument(
            'targets',
            InputArgument::IS_ARRAY | InputArgument::REQUIRED,
            'Which target(s) should be used?'
        );
    }

    /**
     * @inheritdoc
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $argumentTargets = $input->getArgument('targets');
        $configurationTargets = array_keys($this->getConfiguration()['targets']);
        $intersect = array_intersect($argumentTargets, $configurationTargets);
        $diff = array_diff($argumentTargets, $intersect);
        if (!empty($diff)) {
            throw new \InvalidArgumentException('Invalid target(s): ' . implode(',', $diff));
        }
    }

    /**
     * @param string $command
     * @param array $targets
     */
    protected function remotePassthru($command, $targets)
    {
        $output = $this->output;
        foreach ($targets as $target) {
            $connection = $this->getTargetSSH($target);
            $output->writeln($target);
            @$connection->exec($command, function ($data) use ($output) {
                $output->write($data);
            });
            $output->writeln('');
        }
    }

    /**
     * @param string $command
     * @param array $targets
     * @return array
     */
    protected function remoteExec($command, $targets)
    {
        $command = $this->appendExitCodeCommand($command);
        $responses = array();
        $targets = is_array($targets) ? $targets : array($targets);
        foreach ($targets as $target) {
            $connection = $this->getTargetSSH($target);
            $responses[$target] = $this->extractExitCodeResponse(@$connection->exec($command), $target);
        }
        return $responses;
    }

    /**
     * @param string $command
     * @return string
     */
    protected function appendExitCodeCommand($command)
    {
        while (';' === substr($command, -1)) {
            $command = substr($command, 0, -1);
        }
        return $command . ';echo $?';
    }

    /**
     * @param string $response
     * @param string $target
     * @return string
     */
    protected function extractExitCodeResponse($response, $target)
    {
        $end  = strrpos($response, "\n");
        $start = strrpos(substr($response, 0, $end), "\n") + 1;
        $exitCode = intval(trim(substr($response, $start, $end)));
        $this->setLastExitCode($target, $exitCode);
        return substr($response, 0, $start);
    }

    /**
     * @param string $target
     * @param integer $exitCode
     */
    protected function setLastExitCode($target, $exitCode)
    {
        self::$exitCodes[$target] = intval(trim($exitCode));
    }

    /**
     * @param string $target
     * @return integer
     */
    protected function getLastExitCode($target)
    {
        if (!array_key_exists($target, self::$exitCodes)) {
            self::$exitCodes[$target] = 0;
        }
        return self::$exitCodes[$target];
    }

    /**
     * @param string $target
     * @return \Net_SSH2
     */
    protected function getTargetSSH($target)
    {
        if (!array_key_exists($target, self::$targetSSH)) {
            $bridge = $this->getTargetBride($target);
            self::$targetSSH[$target] = $bridge->ssh();
        }
        return self::$targetSSH[$target];
    }

    /**
     * @param string $target
     * @return \Net_SCP
     */
    protected function getTargetSCP($target)
    {
        if (!array_key_exists($target, self::$targetSCP)) {
            $bridge = $this->getTargetBride($target);
            self::$targetSCP[$target] = $bridge->scp();
        }
        return self::$targetSCP[$target];
    }

    /**
     * @param string $target
     * @return Bridge
     */
    protected function getTargetBride($target)
    {
        $configuration = $this->getConfiguration($target);
        $params = $configuration['connection'];
        $bridge = new Bridge();
        $bridge->setAuth(Bridge::AUTH_PASSWORD);
        $bridge->setHostname($params['hostname']);
        $bridge->setPort($params['port']);
        $bridge->setTimeout($params['timeout']);
        $bridge->setUsername($params['username']);
        if (!empty($params['password'])) {
            $bridge->setPassword($params['password']);
        }
        if (!empty($params['passwordfile'])) {
            $bridge->setPasswordfile($params['passwordfile']);
        }
        if (!empty($params['keyfile'])) {
            $bridge->setAuth(Bridge::AUTH_KEYFILE);
            $bridge->setKeyfile($params['keyfile']);
        }
        return $bridge;
    }

    /**
     * @param string $target
     * @param string $password
     * @return string
     */
    protected function createPasswordfile($target, $password)
    {
        $file = sys_get_temp_dir() . '/' . $target;
        !file_exists($file) ?: unlink($file);
        file_put_contents($file, $password);
        return $file;
    }

    /**
     * @param string $target
     */
    protected function removePasswordfile($target)
    {
        $file = sys_get_temp_dir() . '/' . $target;
        !file_exists($file) ?: unlink($file);
    }

    /**
     * @param string $target
     * @return string
     */
    protected function getDeployPath($target)
    {
        $deployPath = $this->getConfiguration($target)['deploy_path'];
        return substr($deployPath, -1) === '/' ? substr($deployPath, 0, -1) : $deployPath;
    }
}