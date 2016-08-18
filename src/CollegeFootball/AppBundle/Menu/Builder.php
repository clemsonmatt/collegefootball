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
        $menu = $factory->createItem('root', [
            'childrenAttributes' => [
                'id'    => 'side-menu',
                'class' => 'nav',
            ],
        ]);

        $menu->addChild('Scoreboard', [
            'route' => 'collegefootball_app_index',
        ]);

        $menu->addChild('Conferences', [
            'route' => 'collegefootball_team_conference_index',
        ]);

        $menu->addChild('Games', [
            'route' => 'collegefootball_team_game_index',
        ]);

        $menu->addChild('Rankings', [
            'route' => 'collegefootball_team_ranking_index',
        ]);

        $menu->addChild('Gameday', [
            'uri' => '#'
        ]);

        $menu->addChild('Pick\'em', [
            'uri' => '#'
        ]);

        return $menu;
    }
}
