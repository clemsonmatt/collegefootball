<?php

namespace CollegeFootball\TeamBundle\Form\Type;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type as SymfonyTypes;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use CollegeFootball\TeamBundle\Entity\Team;

class TeamType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', SymfonyTypes\TextType::class);

        $builder->add('nameShort', SymfonyTypes\TextType::class);

        $builder->add('nameAbbr', SymfonyTypes\TextType::class);

        $builder->add('mascot', SymfonyTypes\TextType::class);

        $builder->add('primaryColor', SymfonyTypes\TextType::class);

        $builder->add('secondaryColor', SymfonyTypes\TextType::class);

        $builder->add('city', SymfonyTypes\TextType::class);

        $builder->add('state', SymfonyTypes\ChoiceType::class, [
            'choices' => array_flip(Team::getStatesList()),
        ]);

        $builder->add('school', SymfonyTypes\TextType::class);

        $builder->add('stadiumName', SymfonyTypes\TextType::class);

        $builder->add('logo', SymfonyTypes\FileType::class, [
            'required' => false,
            'mapped'   => false,
        ]);

        $builder->add('conference', EntityType::class, [
            'class'    => 'CollegeFootballTeamBundle:Conference',
            'group_by' => 'division',
        ]);

        $builder->add('subConference', SymfonyTypes\TextType::class, [
            'required' => false,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Team::class
        ]);
    }
}
