<?php

namespace AppBundle\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

use AppBundle\Service\WeekService;

class Builder implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function mainMenu(FactoryInterface $factory, array $options)
    {
        $user        = $this->container->get('security.token_storage')->getToken()->getUser();
        $weekSerivce = $this->container->get(WeekService::class);
        $currentWeek = $weekSerivce->currentWeek();

        $menu = $factory->createItem('root', [
            'childrenAttributes' => [
                'class' => 'nav navbar-nav',
            ],
        ]);

        $menu->addChild('Scoreboard', [
            'route' => 'app_index',
        ]);

        $menu->addChild('Pick\'em', [
            'route'           => 'app_person_show',
            'routeParameters' => ['username' => $user->getUsername()],
        ]);

        $menu->addChild('Conferences', [
            'route' => 'app_conference_index',
        ]);

        $menu->addChild('Games', [
            'route' => 'app_game_index',
        ]);

        // $menu->addChild('Predictor', [
        //     'route'           => 'app_game_lines',
        //     'routeParameters' => [
        //         'season' => $currentWeek['season'],
        //         'week'   => $currentWeek['week']->getNumber(),
        //     ],
        // ]);

        $menu->addChild('Rankings', [
            'route' => 'app_ranking_index',
        ]);

        $menu->addChild('Gameday', [
            'route' => 'app_gameday_index',
        ]);

        return $menu;
    }

    public function teamMenu(FactoryInterface $factory, array $options)
    {
        $menu = $factory->createItem('root', [
            'childrenAttributes' => [
                'class' => 'navbar navbar-nav',
            ],
        ]);

        $menu->addChild('Overview', [
            'route'           => 'app_team_show',
            'routeParameters' => ['slug' => $options['team']->getSlug()]
        ])->setAttribute('icon', 'star');

        $menu->addChild('Schedule', [
            'route'           => 'app_team_schedule',
            'routeParameters' => ['slug' => $options['team']->getSlug()]
        ])->setAttribute('icon', 'calendar');

        $menu->addChild('Rankings', [
            'route'           => 'app_team_rankings',
            'routeParameters' => ['slug' => $options['team']->getSlug()]
        ])->setAttribute('icon', 'arrow-graph-up-right');

        $menu->addChild('Statistics', [
            'route'           => 'app_team_statistics',
            'routeParameters' => ['slug' => $options['team']->getSlug()]
        ])->setAttribute('icon', 'stats-bars');

        return $menu;
    }
}
