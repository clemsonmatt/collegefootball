<?php

namespace CollegeFootball\TeamBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type as SymfonyTypes;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use CollegeFootball\TeamBundle\Entity\Game;

class GameType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('date', SymfonyTypes\DateType::class, [
            'widget'      => 'single_text',
            'placeholder' => 'mm/dd/yyyy',
        ]);

        $builder->add('time', SymfonyTypes\TextType::class, [
            'required' => false,
        ]);

        $builder->add('season', SymfonyTypes\IntegerType::class, [
            'data' => date('Y'),
        ]);

        $builder->add('homeTeam', EntityType::class, [
            'class'         => 'CollegeFootballTeamBundle:Team',
            'placeholder'   => '-- Home Team --',
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('t')
                    ->join('t.conference', 'c')
                    ->orderBy('c.name', 'ASC')
                    ->addOrderBy('t.name', 'ASC');
            },
            'group_by'     => 'conference',
            'choice_label' => 'name',
        ]);

        $builder->add('awayTeam', EntityType::class, [
            'class'         => 'CollegeFootballTeamBundle:Team',
            'placeholder'   => '-- Away Team --',
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('t')
                    ->join('t.conference', 'c')
                    ->orderBy('c.name', 'ASC')
                    ->addOrderBy('t.name', 'ASC');
            },
            'group_by'     => 'conference',
            'choice_label' => 'name',
        ]);

        $builder->add('location', SymfonyTypes\TextType::class, [
            'required' => false,
        ]);

        $builder->add('spread', SymfonyTypes\TextType::class, [
            'required' => false,
        ]);

        $builder->add('predictedWinner', SymfonyTypes\ChoiceType::class, [
            'placeholder' => '-- Predicted Winner --',
            'required'    => false,
            'choices'     => [
                'Away' => 'Away',
                'Home' => 'Home'
            ],
        ]);

        $builder->add('conferenceChampionship', SymfonyTypes\CheckboxType::class, [
            'required' => false,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Game::class,
        ]);
    }
}
