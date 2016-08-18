<?php

namespace CollegeFootball\TeamBundle\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class Builder implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function teamMenu(FactoryInterface $factory, array $options)
    {
        $menu = $factory->createItem('root', [
            'childrenAttributes' => [
                'class' => 'navbar navbar-nav',
            ],
        ]);

        $menu->addChild('Overview', [
            'route'           => 'collegefootball_team_show',
            'routeParameters' => ['slug' => $options['team']->getSlug()]
        ])->setAttribute('icon', 'star');

        $menu->addChild('Schedule', [
            'route'           => 'collegefootball_team_schedule',
            'routeParameters' => ['slug' => $options['team']->getSlug()]
        ])->setAttribute('icon', 'calendar');

        $menu->addChild('Rankings', [
            'uri' => '#'
        ])->setAttribute('icon', 'arrow-graph-up-right');

        $menu->addChild('Statistics', [
            'route'           => 'collegefootball_team_statistics',
            'routeParameters' => ['slug' => $options['team']->getSlug()]
        ])->setAttribute('icon', 'stats-bars');

        $menu->addChild('Roster', [
            'uri' => '#'
        ])->setAttribute('icon', 'clipboard');

        return $menu;
    }
}
