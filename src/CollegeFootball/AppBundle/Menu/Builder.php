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
            'route' => 'collegefootball_gameday_index',
        ]);

        if ($authorizationChecker->isGranted('ROLE_MANAGE')) {
            $menu->addChild('People', [
                'route' => 'collegefootball_manage_people',
            ]);
        }

        return $menu;
    }
}
