<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

use AppBundle\Entity\Game;
use AppBundle\Entity\Team;

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
        foreach (range(1, 14) as $weekNumber) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_URL, 'https://www.espn.com/college-football/schedule/_/week/'.$weekNumber);

            $response = curl_exec($ch);
            curl_close($ch);

            $weeks[] = $response;
        }

        $counter = 0;

        $container = $this->getContainer();
        $em        = $container->get('doctrine.orm.default_entity_manager');

        $repository     = $em->getRepository('AppBundle:Team');
        $gameRepository = $em->getRepository('AppBundle:Game');

        foreach ($weeks as $result) {
            $dateString = $this->getStringBetween($result, '<h2 class="table-caption">', '</h2>');
            $result     = $this->getStringBetween($result, '<table class="schedule', '</table>', true);

            while ($result['between']) {
                $dayGames = $this->getStringBetween($result['between'], '<tr', '</tr>', true);

                while ($dayGames['between']) {
                    $gameRaw = $dayGames['between'];

                    if ($gameRaw[0] == '>') {
                        // this is the thead for each day -- get the next which is in tbody
                        $dayGames = $this->getStringBetween($dayGames['rest'], '<tr', '</tr>', true);
                        $gameRaw  = $dayGames['between'];
                    }

                    $awayTeamRaw  = $this->getStringBetween($gameRaw, '<a  name="&lpos=college-football:schedule:team" class="team-name"', '</a>');
                    $awayTeamName = $this->getStringBetween($awayTeamRaw, '<span>', '</span>');

                    $homeTeamRaw  = $this->getStringBetween($gameRaw, '<div class="home-wrapper"', '</td>');
                    $homeTeamName = $this->getStringBetween($homeTeamRaw, '<span>', '</span>');

                    $espnId   = $this->getStringBetween($gameRaw, 'href="/college-football/game/_/gameId/', '">');

                    $timeRaw  = $this->getStringBetween($gameRaw, '<td data-behavior="date_time"', '</td>');
                    $dateTime = $this->getStringBetween($timeRaw, 'data-date="', '">');
                    if ($dateTime) {
                        $dateTime = new \DateTime($dateTime, new \DateTimeZone('UTC'));
                        $dateTime = $dateTime->setTimezone(new \DateTimeZone('America/New_York'))->format('h:i A');
                    } else {
                        $dateTime = 'TBD';
                    }

                    $network = $this->getStringBetween($gameRaw, '<td class="network">', '</td>');
                    if (strpos($network, 'alt="')) {
                        // ESPN network
                        $network = $this->getStringBetween($network, 'alt="', '"');
                    }

                    $location = $this->getStringBetween($gameRaw, '<td class="schedule-location">', '</td>');
                    if (strpos($location, '<a') !== false) {
                        // remove the link
                        $location = $this->getStringBetween($location, '>', '</a>', true);
                        $location = $location['between'].str_replace(['</a>', ' </a>'], '', $location['rest']);
                    }

                    $game = $gameRepository->findOneByEspnId($espnId);
                    if ($game && $input->getArgument('context') == 'import') {
                        // update the game if previously added
                        if ($game->getLocation() != $location || $game->getNetwork() != $network || ($game->getTime() != $dateTime && $dateTime != 'TBD')) {
                            $output->writeln('update: '.$game->getId().' - '.$awayTeamName.' at '.$homeTeamName);
                            $game->setLocation($location);
                            $game->setNetwork($network);

                            if ($dateTime != 'TBD') {
                                $game->setTime($dateTime);
                            }
                        }
                    } else {
                        // add the game
                        $awayTeam = $repository->findOneByNameShort($awayTeamName);
                        $homeTeam = $repository->findOneByNameShort($homeTeamName);

                        if ($awayTeam && $homeTeam) {
                            if ($input->getArgument('context') == 'import') {
                                $newGame = new Game();

                                $newDate = new \DateTime($dateString);
                                $newGame->setDate($newDate);

                                $newGame->setSeason(date('Y'));
                                $newGame->setAwayTeam($awayTeam);
                                $newGame->setHomeTeam($homeTeam);
                                $newGame->setLocation($location);
                                $newGame->setEspnId($espnId);
                                $newGame->setNetwork($network);

                                if ($dateTime != 'TBD') {
                                    $newGame->setTime($dateTime);
                                }

                                $em->persist($newGame);

                                $counter++;
                            }
                        } else {
                            $output->writeln('==========');
                            $output->writeln('date: '.$dateString);
                            $output->writeln('awayTeam: '.$awayTeamName);
                            $output->writeln('homeTeam: '.$homeTeamName);
                            $output->writeln('location: '.$location);
                            $output->writeln('time: '.$dateTime);
                            $output->writeln('espnId: '.$espnId);
                            $output->writeln('==========');
                        }
                    }

                    $dayGames = $this->getStringBetween($dayGames['rest'], '<tr', '</tr>', true);
                }

                $dateString = $dateString = $this->getStringBetween($result['rest'], '<h2 class="table-caption">', '</h2>');
                $result     = $this->getStringBetween($result['rest'], '<table class="schedule', '</table>', true);
            }
        }

        $em->flush();
        $output->writeln($counter.' Games imported');
    }

    private function getStringBetween($string, $start, $end, $returnRest = false)
    {
        $ini = strpos($string, $start);

        if ($ini == 0) {
            return null;
        }

        $ini += strlen($start);
        $len = strpos($string, $end, $ini) - $ini;

        if (! $returnRest) {
            return substr($string, $ini, $len);
        }

        return [
            'between' => substr($string, $ini, $len),
            'rest'    => substr($string, $ini + $len)
        ];
    }
}
