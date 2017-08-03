<?php

namespace CollegeFootball\TeamBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type as SymfonyTypes;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TeamStatsType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /* points */
        $builder->add('pointsFinal', SymfonyTypes\IntegerType::class, ['label' => 'Final']);
        $builder->add('pointsFirst', SymfonyTypes\IntegerType::class, ['label' => '1st quarter']);
        $builder->add('pointsSecond', SymfonyTypes\IntegerType::class, ['label' => '2nd quarter']);
        $builder->add('pointsThird', SymfonyTypes\IntegerType::class, ['label' => '3rd quarter']);
        $builder->add('pointsFourth', SymfonyTypes\IntegerType::class, ['label' => '4th quarter']);

        /* OT */
        $builder->add('ot', SymfonyTypes\IntegerType::class, ['label' => 'OT', 'required' => false]);

        /* rushing */
        $builder->add('rushingYards', SymfonyTypes\IntegerType::class, ['label' => 'Yards']);
        $builder->add('rushingAttempts', SymfonyTypes\IntegerType::class, ['label' => 'Attempts']);

        /* passing */
        $builder->add('passingYards', SymfonyTypes\IntegerType::class, ['label' => 'Yards']);
        $builder->add('passingAttempts', SymfonyTypes\IntegerType::class, ['label' => 'Attempts']);
        $builder->add('passingCompletions', SymfonyTypes\IntegerType::class, ['label' => 'Completions']);

        /* total offense */
        $builder->add('totalOffenseYards', SymfonyTypes\IntegerType::class, ['label' => 'Yards']);

        /* turnovers */
        $builder->add('turnoverCount', SymfonyTypes\IntegerType::class);

        /* penalties */
        $builder->add('penaltyYards', SymfonyTypes\IntegerType::class, ['label' => 'Yards']);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([]);
    }
}
