<?php

namespace CollegeFootball\AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type as SymfonyTypes;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use CollegeFootball\AppBundle\Entity\Person;

class PersonType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('firstName', SymfonyTypes\TextType::class, [
            'label' => false,
            'attr'  => ['placeholder' => 'First Name'],
        ]);

        $builder->add('lastName', SymfonyTypes\TextType::class, [
            'label' => false,
            'attr'  => ['placeholder' => 'Last Name'],
        ]);

        $builder->add('email', SymfonyTypes\EmailType::class, [
            'label' => false,
            'attr'  => ['placeholder' => 'Email'],
        ]);

        $builder->add('username', SymfonyTypes\TextType::class, [
            'label' => false,
            'attr'  => ['placeholder' => 'Username'],
        ]);

        $builder->add('password', SymfonyTypes\RepeatedType::class, [
            'type'            => SymfonyTypes\PasswordType::class,
            'invalid_message' => 'The password fields must match.',
            'required'        => true,
            'options'         => ['attr' => ['class' => 'password-field']],
            'first_options'   => [
                'label' => false,
                'attr'  => ['placeholder' => 'Password'],
            ],
            'second_options'  => [
                'label' => false,
                'attr'  => ['placeholder' => 'Repeat Password'],
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Person::class,
        ]);
    }
}
