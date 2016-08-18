<?php

namespace CollegeFootball\TeamBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

use CollegeFootball\TeamBundle\Entity\Game;
use CollegeFootball\TeamBundle\Entity\Team;

class PopulateGamesCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('collegefootball:team:games')
            ->setDescription('Makes all the games in the db')
            ->addArgument('context', InputArgument::REQUIRED, 'Missing context: "test" or "import"')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $finder    = new Finder();
        $jsonFiles = $finder->files()->in('/Users/matte/Projects/myProjects/collegefootball.dev/src/Notes/Games/'.date('Y'));

        foreach ($jsonFiles as $jsonFile) {
            $weeks[] = json_decode($jsonFile->getContents());
        }

        $counter = 0;

        $container = $this->getContainer();
        $em        = $container->get('doctrine.orm.default_entity_manager');

        $repository = $em->getRepository('CollegeFootballTeamBundle:Team');

        foreach ($weeks as $weekGames) {
            foreach ($weekGames->games as $game) {
                $awayTeam = $repository->findOneByNameShort($game->awayTeam);
                $homeTeam = $repository->findOneByNameShort($game->homeTeam);

                if ($awayTeam && $homeTeam) {
                    if ($input->getArgument('context') == 'import') {
                        $newGame = new Game();

                        $newDate = new \DateTime($game->date);
                        $newGame->setDate($newDate);

                        $newGame->setSeason(date('Y'));
                        $newGame->setAwayTeam($awayTeam);
                        $newGame->setHomeTeam($homeTeam);
                        $newGame->setLocation($game->location);

                        if ($game->time != 'TBD') {
                            $newGame->setTime($game->time);
                        }

                        $em->persist($newGame);

                        $counter++;
                    }
                } else {
                    $output->writeln('==========');
                    $output->writeln('date: '.$game->date);
                    $output->writeln('awayTeam: '.$game->awayTeam);
                    $output->writeln('homeTeam: '.$game->homeTeam);
                    $output->writeln('location: '.$game->location);
                    $output->writeln('time: '.$game->time);
                    $output->writeln('==========');
                }

                $em->flush();
            }
        }

        $output->writeln($counter.' Games imported');
    }
}
