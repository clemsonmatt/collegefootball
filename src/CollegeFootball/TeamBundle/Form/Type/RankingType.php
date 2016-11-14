<?php

namespace CollegeFootball\TeamBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type as SymfonyTypes;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use CollegeFootball\TeamBundle\Entity\Ranking;

class RankingType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('team', EntityType::class, [
            'class'         => 'CollegeFootballTeamBundle:Team',
            'placeholder'   => '-- Team --',
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('t')
                    ->join('t.conference', 'c')
                    ->orderBy('c.name', 'ASC')
                    ->addOrderBy('t.name', 'ASC');
            },
            'group_by' => 'conference',
            'attr'     => ['class' => 'form-control']
        ]);

        if ($options['rank_type'] == 'apRank') {
            $builder->add('apRank', SymfonyTypes\IntegerType::class, [
                'attr'     => ['class' => 'form-control'],
                'required' => false,
            ]);
        }

        if ($options['rank_type'] == 'coachesPollRank') {
            $builder->add('coachesPollRank', SymfonyTypes\IntegerType::class, [
                'attr'     => ['class' => 'form-control'],
                'required' => false,
            ]);
        }

        if ($options['rank_type'] == 'playoffRank') {
            $builder->add('playoffRank', SymfonyTypes\IntegerType::class, [
                'attr'     => ['class' => 'form-control'],
                'required' => false,
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Ranking::class,
        ]);

        $resolver->setRequired(['rank_type']);
    }
}
