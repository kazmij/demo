<?php

namespace Webffilm\UserBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Webffilm\UserBundle\Entity\UserSubscription;
use Webffilm\UserBundle\Entity\UserCodes;
use Webffilm\UserBundle\Entity\User;
use Doctrine\ORM\EntityManager;

class CopyLangCommand extends ContainerAwareCommand {

    public $entities = array(
        'Webffilm\BaseBundle\Entity\ContestTranslation',
        'Webffilm\BaseBundle\Entity\MenuTranslation',
        'Webffilm\BaseBundle\Entity\PageTranslation',
        'Webffilm\BaseBundle\Entity\PrizeTranslation',
        'Webffilm\BaseBundle\Entity\ReferenceTranslation',
        'Webffilm\BaseBundle\Entity\ScenarioCategoryTranslation',
        'Webffilm\BaseBundle\Entity\SeoTranslation',
        'Webffilm\BaseBundle\Entity\SubscriptionIntervalTranslation',
    );

    protected function configure() {
        $this
                ->setName('copy:lang')
                ->setDescription('Copy lang')
                ->addArgument('langStart', InputArgument::REQUIRED, 'Translation language')
                ->addArgument('langTo', InputArgument::REQUIRED, 'Translation language to');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $locales = $this->getContainer()->getParameter('locales');
        $langStart = $input->getArgument('langStart');
        $langTo = $input->getArgument('langTo');
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        foreach ($this->entities as $entity) {
            $rows = $em->getRepository($this->repositoryName($entity))->findByLocale($langStart);
            if (count($rows) > 0) {
                foreach ($rows as $row) {
                    if ($row->getTranslatable()) {
                        $isset = $em->getRepository($this->repositoryName($entity))->findOneBy(array('locale' => $langTo, 'translatable' => $row->getTranslatable()->getId()));
                        if (!$isset) {
                            $vars = get_object_vars($row);
                            $newRow = new $entity();
                            $newRow->setTranslatable($row->getTranslatable());
                            $newRow->setLocale($langTo);
                            foreach ($vars as $k => $v) {
                                if (!in_array($k, array('id', 'locale'))) {
                                    $newRow->$k = $v;
                                }
                            }
                            $em->persist($newRow);
                            $em->flush();
                        }
                    }
                }
            }
        }
        $output->writeln('DONE!');
    }

    /*
     * Normalize entity name to getRepository function argument 
     */

    private function repositoryName($entity) {
        return str_replace(array('\\', 'Entity'), array('', ':'), $entity);
    }

}
