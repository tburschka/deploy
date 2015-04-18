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
    protected static $targetConnections = array();

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
     * @param OutputInterface $output
     */
    protected function remotePassthru($command, $targets, $output)
    {
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
        $output = array();
        $targets = is_array($targets) ? $targets : array($targets);
        foreach ($targets as $target) {
            $connection = $this->getTargetSSH($target);
            $output[$target] = @$connection->exec($command);
        }
        return $output;
    }

    /**
     * @param string $target
     * @return integer
     */
    protected function getLastExitCode($target)
    {
        return intval(trim($this->getTargetSSH($target)->exec('echo $?')));
    }

    /**
     * @param string $target
     * @return \Net_SSH2
     */
    protected function getTargetSSH($target)
    {
        if (!array_key_exists($target, self::$targetConnections)) {
            $configuration = $this->getConfiguration();

            $params = $configuration['targets'][$target]['connection'];
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
            self::$targetConnections[$target] = $bridge->ssh();
        }
        return self::$targetConnections[$target];
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
        return substr($deployPath, -1) === '/' ? $deployPath : $deployPath . '/';
    }
}