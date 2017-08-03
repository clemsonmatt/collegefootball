<?php

namespace CollegeFootball\AppBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
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
        if (! $options['only_password']) {
            $builder->add('firstName', SymfonyTypes\TextType::class, [
                'label' => false,
                'attr'  => ['placeholder' => 'First Name'],
            ]);

            $builder->add('lastName', SymfonyTypes\TextType::class, [
                'label' => false,
                'attr'  => ['placeholder' => 'Last Name'],
            ]);

            $builder->add('email', SymfonyTypes\EmailType::class, [
                'label'    => false,
                'attr'     => ['placeholder' => 'Email'],
                'required' => false,
            ]);

            if (! $options['create']) {
                $builder->add('username', SymfonyTypes\TextType::class, [
                    'label' => false,
                    'attr'  => ['placeholder' => 'Username'],
                ]);
            }
        } else {
            $builder->add('currentPassword', SymfonyTypes\PasswordType::class, [
                'attr'   => ['placeholder' => 'Current Password'],
                'mapped' => false,
            ]);
        }

        if ($options['show_password'] || $options['only_password']) {
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
        } else {
            $builder->add('team', EntityType::class, [
                'class'         => 'CollegeFootballTeamBundle:Team',
                'placeholder'   => '-- My Team --',
                'choice_label'  => 'name',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('t')
                        ->join('t.conference', 'c')
                        ->orderBy('c.name', 'ASC')
                        ->addOrderBy('t.name', 'ASC');
                },
                'group_by' => 'conference',
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
            'data_class'    => Person::class,
            'show_password' => true,
            'only_password' => false,
            'create'        => false,
        ]);
    }
}
