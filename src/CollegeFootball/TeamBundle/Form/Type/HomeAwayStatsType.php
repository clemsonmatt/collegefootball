<?php

namespace CollegeFootball\TeamBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use CollegeFootball\TeamBundle\Form\Type\TeamStatsType;

class HomeAwayStatsType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('homeStats', TeamStatsType::class);

        $builder->add('awayStats', TeamStatsType::class);
    }
}
