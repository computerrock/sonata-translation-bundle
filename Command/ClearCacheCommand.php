<?php

namespace Computerrock\SonataTranslationBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ClearCacheCommand
 * @package Computerrock\SonataTranslationBundle\Command
 */
class ClearCacheCommand extends ContainerAwareCommand
{
    /**
     *
     */
    public function configure()
    {
        $this
            ->setName('ibrows:sonatatranslationbundle:clearcache');
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $locales = $this->getManagedLocales();
        $output->writeln('Remove cache for translations in: '. implode(", ", $locales));
        $this->getContainer()->get('translator')->removeLocalesCacheFiles($locales);
    }

    /**
     * @return array
     */
    protected function getManagedLocales()
    {
        return $this->getContainer()->getParameter('lexik_translation.managed_locales');
    }
}