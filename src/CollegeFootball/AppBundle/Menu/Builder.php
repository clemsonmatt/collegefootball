<?php

namespace CollegeFootball\AppBundle\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class Builder implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function mainMenu(FactoryInterface $factory, array $options)
    {
        $authorizationChecker = $this->container->get('security.authorization_checker');
        $user                 = $this->container->get('security.token_storage')->getToken()->getUser();
        $weekSerivce          = $this->container->get('collegefootball.team.week');
        $currentWeek          = $weekSerivce->currentWeek();

        $menu = $factory->createItem('root', [
            'childrenAttributes' => [
                // 'id'    => 'side-menu',
                'class' => 'nav navbar-nav',
            ],
        ]);

        $menu->addChild('Scoreboard', [
            'route' => 'collegefootball_app_index',
        ]);

        $menu->addChild('Pick\'em', [
            'route'           => 'collegefootball_person_show',
            'routeParameters' => ['username' => $user->getUsername()],
        ]);

        $menu->addChild('Conferences', [
            'route' => 'collegefootball_team_conference_index',
        ]);

        $menu->addChild('Games', [
            'route' => 'collegefootball_team_game_index',
        ]);

        // $menu->addChild('Predictor', [
        //     'route'           => 'collegefootball_team_game_lines',
        //     'routeParameters' => [
        //         'season' => $currentWeek['season'],
        //         'week'   => $currentWeek['week']->getNumber(),
        //     ],
        // ]);

        $menu->addChild('Rankings', [
            'route' => 'collegefootball_team_ranking_index',
        ]);

        $menu->addChild('Gameday', [
            'route' => 'collegefootball_gameday_index',
        ]);

        // if ($authorizationChecker->isGranted('ROLE_MANAGE')) {
        //     $menu->addChild('Game Stats', [
        //         'route' => 'collegefootball_team_game_stats_index',
        //     ]);

        //     $menu->addChild('People', [
        //         'route' => 'collegefootball_manage_people',
        //     ]);
        // }

        return $menu;
    }
}
