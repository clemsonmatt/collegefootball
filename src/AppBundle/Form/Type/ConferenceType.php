<?php

namespace AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type as SymfonyTypes;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use AppBundle\Entity\Conference;

class ConferenceType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', SymfonyTypes\TextType::class);

        $builder->add('nameShort', SymfonyTypes\TextType::class);

        $builder->add('division', SymfonyTypes\ChoiceType::class, [
            'choices' => [
                'FBS (Division I-A Conferences)'  => 'FBS (Division I-A Conferences)',
                'FCS (Division I-AA Conferences)' => 'FCS (Division I-AA Conferences)',
            ],
        ]);

        $builder->add('subConferences', SymfonyTypes\CollectionType::class, [
            'entry_type'    => SymfonyTypes\TextType::class,
            'allow_add'     => true,
            'allow_delete'  => true,
            'prototype'     => true,
            'entry_options' => [
                'attr' => ['class' => 'form-control']
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Conference::class,
        ]);
    }
}
