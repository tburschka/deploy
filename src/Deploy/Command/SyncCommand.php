<?php

namespace Deploy\Command;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SyncCommand extends AbstractRemoteCommand
{
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('sync')
            ->addOption('release-name', null, InputOption::VALUE_OPTIONAL, 'release name')
        ;
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
            $output->writeln('== target: ' . $target);

            // get file
            $configuration = $this->getConfiguration($target);
            $localfile = $configuration['deploy_file'];
            if (!file_exists($localfile)) {
                $output->writeln('== file not found: ' . $localfile);
                $output->writeln('== ERROR');
                return 1;
            }

            // transfer file
            $size = filesize($localfile);
            $progess = new ProgressBar($output, $size);
            $remotefile = $this->getDeployPath($target) . '/releases/' . basename($localfile);
            $scp = $this->getTargetSCP($target);
            @$scp->put(
                $this->getDeployPath($target) . '/releases/' . basename($localfile),
                $localfile,
                NET_SCP_LOCAL_FILE,
                function ($sent) use ($progess) {
                    $progess->setProgress($sent);
                }
            );
            $output->writeln('');

            // validate transferred file
            $validateUploadCommand = 'php -r "exit(\'%s\' !== md5_file(\'%s\'));"';
            $this->remoteExec(sprintf($validateUploadCommand, md5_file($localfile), $remotefile), [$target]);
            if ($this->getLastExitCode($target)) {
                $output->writeln('== md5 mismatch');
                $output->writeln('== ERROR');
                return 1;
            } else {
                $output->writeln('');
            }


            $releaseName = is_null($input->getOption('release-name')) ? time() : $input->getOption('release-name');
            // unpack file
            $targetDir = $this->getDeployPath($target) . '/releases/' . $releaseName;
            $this->remoteExec('mkdir ' . $targetDir, $target);
            if ($this->getLastExitCode($target)) {
                $output->writeln('== could not create target dir');
                return 1;
            }
            $unpackCommand = 'tar -xzf ' . $remotefile . ' -C ' . $targetDir;
            $this->remoteExec($unpackCommand, $target);
            $output->writeln($unpackCommand);


        }
        return 0;
    }
}