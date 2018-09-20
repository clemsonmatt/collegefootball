<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use AppBundle\Entity\Week;
use AppBundle\Service\WeeklyScoresService;

class WeeklyScoresCommand extends ContainerAwareCommand
{
    private $weeklyScoresService;

    public function __construct(WeeklyScoresService $weeklyScoresService)
    {
        $this->weeklyScoresService = $weeklyScoresService;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('collegefootball:weekly-scores')
            ->setDescription('Sets the weekly stats')
            ->addArgument('week', InputArgument::REQUIRED, 'Missing week ID')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container  = $this->getContainer();
        $em         = $container->get('doctrine.orm.default_entity_manager');
        $repository = $em->getRepository(Week::class);
        $week       = $repository->findOneById($input->getArgument('week'));

        $this->weeklyScoresService->importScores($week);
    }
}
