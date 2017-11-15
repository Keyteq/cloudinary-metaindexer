<?php
/**
 * Created by PhpStorm.
 * User: petterkjelkenes
 * Date: 14.11.2017
 * Time: 16.05
 */

namespace Keyteq\Bundle\CloudinaryMetaIndexer\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SyncCloudinaryCommand extends ContainerAwareCommand
{

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this->setName('keyteq:cloudinary-meta-indexer:sync')
            ->setDescription('Syncronizes data from cloudinary to mongodb');
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $syncService = $container->get('keyteq.cloudinary_meta_indexer.sync');
        $syncService->sync($output);
    }

}