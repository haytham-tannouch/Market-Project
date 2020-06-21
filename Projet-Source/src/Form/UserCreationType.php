<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{CheckboxType,
    ChoiceType,
    DateType,
    EmailType,
    PasswordType,
    SubmitType,
    TextType};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserCreationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email',         EmailType::class)
            ->add('password',      PasswordType::class)
            ->add('role',          ChoiceType::class, array(
                'choices' => array(
                    'Admin' => 'Administrateur',
                    'Editeur' => 'Editeur',
                ),
            ))
            ->add('nom',            TextType::class)
            ->add('prenom',         TextType::class)
            ->add('telephone',      TextType::class)
            ->add('dateNaissance',  DateType::class)
            ->add('poste',          TextType::class)
            ->add('save',           SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
