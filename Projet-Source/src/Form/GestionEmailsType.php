<?php

namespace App\Form;

use App\Entity\Email;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GestionEmailsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('Type',TextType::class, array(
                'attr' => array(
                    'class' => 'form-control'
                )))
            ->add('Main',TextareaType::class, array(
                'attr' => array(
                    'class' => 'form-control'
                )))
            ->add('User',          ChoiceType::class, array(
                'choices' => array(
                    'Administrateur' => 'Administrateur',
                    'Editeur' => 'Editeur',

                ),
                'attr'=>array(
                    'class' => 'form-control'
                )))

            ->add('Sauvegarder', SubmitType::class,array(
                'attr'=>array(
                    'class'=>'btn btn-primary',
                )
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Email::class,
        ]);
    }
}
