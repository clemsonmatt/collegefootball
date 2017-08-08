<?php

namespace CollegeFootball\AppBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type as SymfonyTypes;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use CollegeFootball\AppBundle\Entity\Gameday;

class GamedayType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('game', EntityType::class, [
            'class'         => 'CollegeFootball\TeamBundle\Entity\Game',
            'placeholder'   => '-- Game --',
            'query_builder' => function (EntityRepository $er) use ($options) {
                return $er->createQueryBuilder('g')
                    ->where('g.date >= :startWeek')
                    ->andWhere('g.date <= :endWeek')
                    ->orderBy('g.time', 'ASC')
                    ->setParameter('startWeek', $options['week']->getStartDate())
                    ->setParameter('endWeek', $options['week']->getEndDate());
            }
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Gameday::class,
        ]);

        $resolver->setRequired(['week']);
    }
}
