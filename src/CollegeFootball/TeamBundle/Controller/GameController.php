<?php

namespace CollegeFootball\TeamBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use CollegeFootball\AppBundle\Entity\Week;
use CollegeFootball\TeamBundle\Entity\Game;
use CollegeFootball\TeamBundle\Entity\Team;
use CollegeFootball\TeamBundle\Form\Type\GameType;

/**
 * @Route("/game")
 */
class GameController extends Controller
{
    /**
     * @Route("/{season}", name="collegefootball_team_game_index", defaults={"season": null})
     * @Route("/{season}/week/{week}", name="collegefootball_team_game_index_week", defaults={"week": null})
     */
    public function indexAction($season = null, $week = null)
    {
        $result      = $this->get('collegefootball.team.week')->currentWeek($season, $week);
        $week        = $result['week'];
        $season      = $result['season'];
        $seasonWeeks = $result['seasonWeeks'];

        $em         = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('CollegeFootballTeamBundle:Game');
        $games      = $repository->findGamesByWeek($week);

        $playoffGames = [];

        if ((string)$week == 'Bowl') {
            foreach ($games as $game) {
                if (strpos($game->getBowlName(), 'CFP Semifinal')) {
                    if (! array_key_exists('firstSemifinal', $playoffGames)) {
                        $playoffGames['firstSemifinal'] = $game;
                    } else {
                        $playoffGames['secondSemifinal'] = $game;
                    }
                } elseif ($game->getBowlName() == 'CFP National Championship Game') {
                    $playoffGames['championship'] = $game;
                }
            }
        }

        return $this->render('CollegeFootballTeamBundle:Game:index.html.twig', [
            'games'         => $games,
            'season'        => $season,
            'week'          => $week,
            'season_weeks'  => $seasonWeeks,
            'playoff_games' => $playoffGames,
        ]);
    }

    /**
     * @Route("/{game}/show", name="collegefootball_team_game_show")
     */
    public function showAction(Game $game)
    {
        $em         = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('CollegeFootballAppBundle:Week');
        $week       = $repository->createQueryBuilder('w')
            ->where('w.startDate <= :date')
            ->andWhere('w.endDate >= :date')
            ->setParameter('date', $game->getDate())
            ->getQuery()
            ->getSingleResult();

        $pickemService   = $this->get('collegefootball.app.pickem');
        $gamePredictions = $pickemService->picksByWeek($week, $game);

        $repository = $em->getRepository('CollegeFootballAppBundle:Prediction');
        $userPrediction = $repository->findOneBy([
            'person' => $this->getUser(),
            'game'   => $game,
        ]);

        $pickemService = $this->get('collegefootball.app.pickem');
        $gamedayPicks  = $pickemService->gamedayWeekPicks($week, $game);

        $statsService   = $this->get('collegefootball.team.stats');
        $gameComparison = $statsService->gameComparison($game);

        return $this->render('CollegeFootballTeamBundle:Game:show.html.twig', [
            'game'             => $game,
            'game_predictions' => $gamePredictions,
            'user_prediction'  => $userPrediction,
            'gameday_picks'    => $gamedayPicks,
            'game_comparison'  => $gameComparison,
        ]);
    }

    /**
     * @Route("/add/single", name="collegefootball_team_game_add")
     * @Route("/add/{slug}/team", name="collegefootball_team_game_add_team")
     * @Security("is_granted('ROLE_MANAGE')")
     */
    public function addAction(Request $request, Team $team = null)
    {
        $games = [];
        if ($team) {
            $em         = $this->getDoctrine()->getManager();
            $repository = $em->getRepository('CollegeFootballTeamBundle:Game');
            $games      = $repository->findGamesByTeam($team);
        }

        $game = new Game();
        $form = $this->createForm(GameType::class, $game);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($game);
            $em->flush();

            $this->addFlash('success', 'Game added');

            if ($team) {
                return $this->redirectToRoute('collegefootball_team_game_add_team', [
                    'slug' => $team->getSlug(),
                ]);
            }

            return $this->redirectToRoute('collegefootball_team_game_show', [
                'game' => $game->getId(),
            ]);
        }

        return $this->render('CollegeFootballTeamBundle:Game:addEdit.html.twig', [
            'form'  => $form->createView(),
            'team'  => $team,
            'games' => $games,
        ]);
    }

    /**
     * @Route("/{game}/edit", name="collegefootball_team_game_edit")
     * @Security("is_granted('ROLE_MANAGE')")
     */
    public function editAction(Game $game, Request $request)
    {
        $form = $this->createForm(GameType::class, $game);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->flush();

            $this->addFlash('success', 'Game saved');
            return $this->redirectToRoute('collegefootball_team_game_show', [
                'game' => $game->getId(),
            ]);
        }

        return $this->render('CollegeFootballTeamBundle:Game:addEdit.html.twig', [
            'form' => $form->createView(),
            'game' => $game,
        ]);
    }

    /**
     * @Route("/{game}/remove", name="collegefootball_team_game_remove")
     * @Security("is_granted('ROLE_MANAGE')")
     */
    public function removeAction(Game $game)
    {
        $em = $this->getDoctrine()->getManager();
        $em->remove($game);
        $em->flush();

        $this->addFlash('warning', 'Game removed');
        return $this->redirectToRoute('collegefootball_team_game_index');
    }

    /**
     * @Route("/team/{slug}/game/{game}/outcome", name="collegefootball_team_game_outcome")
     * @Security("is_granted('ROLE_MANAGE')")
     */
    public function outcomeAction(Team $team, Game $game)
    {
        $game->setWinningTeam($team);

        $em = $this->getDoctrine()->getManager();
        $em->flush();

        $this->addFlash('success', 'Winning team set');
        return $this->redirectToRoute('collegefootball_team_game_show', [
            'game' => $game->getId(),
        ]);
    }

    /**
     * @Route("/{season}/week/{week}/lines", name="collegefootball_team_game_lines")
     */
    public function linesAction($season = null, $week = null)
    {
        $result      = $this->get('collegefootball.team.week')->currentWeek($season, $week);
        $week        = $result['week'];
        $season      = $result['season'];
        $seasonWeeks = $result['seasonWeeks'];

        $em         = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('CollegeFootballTeamBundle:Game');
        $games      = $repository->findGamesByWeek($week);

        $pickemService = $this->get('collegefootball.app.pickem');
        $gamePicks     = $pickemService->picksByWeek($week);

        $statsService      = $this->get('collegefootball.team.stats');
        $calculatedWinners = $statsService->gameWinners($games);

        $guessedCorrect      = [];
        $guessedCorrectCount = 0;

        foreach ($games as $game) {
            $guessedCorrect[$game->getId()] = false;

            $winningChance = $game->getWinningChance();

            if ($winningChance !== null) {
                $awayChance = $winningChance['away'];
                $homeChance = $winningChance['home'];

                $calculatedWinners[$game->getId()]['awayChance'] = $awayChance;
                $calculatedWinners[$game->getId()]['homeChance'] = $homeChance;
            } else {
                $awayChance = $calculatedWinners[$game->getId()]['awayChance'];
                $homeChance = $calculatedWinners[$game->getId()]['homeChance'];
            }

            if (($awayChance > $homeChance and $game->getWinningTeam() == $game->getAwayTeam()) or ($homeChance > $awayChance and $game->getWinningTeam() == $game->getHomeTeam())) {
                $guessedCorrect[$game->getId()] = true;
                $guessedCorrectCount++;
            }
        }

        return $this->render('CollegeFootballTeamBundle:Game:lines.html.twig', [
            'games'                 => $games,
            'season'                => $season,
            'week'                  => $week,
            'season_weeks'          => $seasonWeeks,
            'game_picks'            => $gamePicks,
            'calculated_winners'    => $calculatedWinners,
            'guessed_correct'       => $guessedCorrect,
            'guessed_correct_count' => $guessedCorrectCount,
        ]);
    }

    /**
     * @Route("/{season}/week/{week}/set-lines", name="collegefootball_team_game_lines_set")
     * @Security("is_granted('ROLE_MANAGE')")
     */
    public function setLinesAction($season = null, $week = null)
    {
        $result      = $this->get('collegefootball.team.week')->currentWeek($season, $week);
        $week        = $result['week'];
        $season      = $result['season'];
        $seasonWeeks = $result['seasonWeeks'];

        $em         = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('CollegeFootballTeamBundle:Game');
        $games      = $repository->findGamesByWeek($week);

        $statsService      = $this->get('collegefootball.team.stats');
        $calculatedWinners = $statsService->gameWinners($games);

        foreach ($games as $game) {
            $winningChance = [
                'home' => $calculatedWinners[$game->getId()]['homeChance'],
                'away' => $calculatedWinners[$game->getId()]['awayChance'],
            ];

            $game->setWinningChance($winningChance);
        }

        $em->flush();

        $this->addFlash('success', 'Lines added');
        return $this->redirectToRoute('collegefootball_team_game_lines', [
            'week'   => $week->getNumber(),
            'season' => $season,
        ]);
    }
}
