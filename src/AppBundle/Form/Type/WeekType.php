<?php

namespace AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type as SymfonyTypes;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use AppBundle\Entity\Week;

class WeekType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('season', SymfonyTypes\IntegerType::class);

        $builder->add('number', SymfonyTypes\IntegerType::class);

        $builder->add('startDate', SymfonyTypes\DateType::class, [
            'widget'      => 'single_text',
            'placeholder' => 'mm/dd/yyyy',
        ]);

        $builder->add('endDate', SymfonyTypes\DateType::class, [
            'widget'      => 'single_text',
            'placeholder' => 'mm/dd/yyyy',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Week::class,
        ]);
    }
}
