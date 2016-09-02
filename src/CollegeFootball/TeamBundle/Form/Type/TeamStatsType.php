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
        $builder->add('ot1', SymfonyTypes\IntegerType::class, ['label' => '1st OT', 'required' => false]);
        $builder->add('ot2', SymfonyTypes\IntegerType::class, ['label' => '2nd OT', 'required' => false]);
        $builder->add('ot3', SymfonyTypes\IntegerType::class, ['label' => '3rd OT', 'required' => false]);

        /* rushing */
        $builder->add('rushingYards', SymfonyTypes\IntegerType::class, ['label' => 'Yards']);
        $builder->add('rushingAttempts', SymfonyTypes\IntegerType::class, ['label' => 'Attempts']);
        $builder->add('rushingTd', SymfonyTypes\IntegerType::class, ['label' => 'TD']);

        /* passing */
        $builder->add('passingYards', SymfonyTypes\IntegerType::class, ['label' => 'Yards']);
        $builder->add('passingAttempts', SymfonyTypes\IntegerType::class, ['label' => 'Attempts']);
        $builder->add('passingCompletions', SymfonyTypes\IntegerType::class, ['label' => 'Completions']);
        $builder->add('passingTd', SymfonyTypes\IntegerType::class, ['label' => 'TD']);
        $builder->add('passingInterceptions', SymfonyTypes\IntegerType::class, ['label' => 'Interceptions']);

        /* total offense */
        $builder->add('totalOffenseYards', SymfonyTypes\IntegerType::class, ['label' => 'Yards']);

        /* punt returns */
        $builder->add('puntReturnCount', SymfonyTypes\IntegerType::class, ['label' => 'Count']);
        $builder->add('puntReturnYards', SymfonyTypes\IntegerType::class, ['label' => 'Yards']);
        $builder->add('puntReturnTd', SymfonyTypes\IntegerType::class, ['label' => 'TD']);

        /* punts */
        $builder->add('puntCount', SymfonyTypes\IntegerType::class, ['label' => 'Count']);
        $builder->add('puntYards', SymfonyTypes\IntegerType::class, ['label' => 'Yards']);

        /* turnovers */
        $builder->add('interceptionCount', SymfonyTypes\IntegerType::class);
        $builder->add('interceptionYards', SymfonyTypes\IntegerType::class);
        $builder->add('interceptionTd', SymfonyTypes\IntegerType::class);
        $builder->add('fumbleCount', SymfonyTypes\IntegerType::class);
        $builder->add('fumbleYards', SymfonyTypes\IntegerType::class);
        $builder->add('fumbleTd', SymfonyTypes\IntegerType::class);

        /* penalties */
        $builder->add('penaltyCount', SymfonyTypes\IntegerType::class, ['label' => 'Count']);
        $builder->add('penaltyYards', SymfonyTypes\IntegerType::class, ['label' => 'Yards']);

        /* time of possession */
        $builder->add('timeOfPossession', SymfonyTypes\TextType::class, ['label' => 'T.O.P.']);

        /* 3rd downs */
        $builder->add('thirdDownAttempts', SymfonyTypes\IntegerType::class, ['label' => 'Attempts']);
        $builder->add('thirdDownConversions', SymfonyTypes\IntegerType::class, ['label' => 'Conversions']);

        /* 4th downs */
        $builder->add('fourthDownAttempts', SymfonyTypes\IntegerType::class, ['label' => 'Attempts']);
        $builder->add('fourthDownConversions', SymfonyTypes\IntegerType::class, ['label' => 'Conversions']);

        /* field goals */
        $builder->add('fieldGoalAttempts', SymfonyTypes\IntegerType::class, ['label' => 'Attempts']);
        $builder->add('fieldGoalMade', SymfonyTypes\IntegerType::class, ['label' => 'Made']);

        /* PAT */
        $builder->add('patAttempts', SymfonyTypes\IntegerType::class, ['label' => 'Attempts']);
        $builder->add('patMade', SymfonyTypes\IntegerType::class, ['label' => 'Made']);

        /* 2pt conversions */
        $builder->add('twoPtConversionAttempts', SymfonyTypes\IntegerType::class, ['label' => 'Attempts']);
        $builder->add('twoPtConversionMade', SymfonyTypes\IntegerType::class, ['label' => 'Made']);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([]);
    }
}
