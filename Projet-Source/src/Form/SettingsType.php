<?php

namespace App\Form;

use App\Entity\Settings;
use DateTime;
use phpDocumentor\Reflection\Types\Integer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{DateType, FileType, IntegerType, SubmitType, TextType};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('NomSite',TextType::class,array(
                'attr' => array(
                    'class' => 'form-control',

                )))
            ->add('Favicon',TextType::class, array(
                'attr' => array(
                    'class' => 'form-control',

                  )))
            ->add('Logo',TextType::class, array(
                'attr' => array(
                    'class' => 'form-control',

                )))
            ->add('DureeSessions',IntegerType::class, array(
                'attr' => array(
                    'class' => 'form-control',

                )))
            ->add('DureeInactivite',IntegerType::class, array(
                'attr' => array(
                    'class' => 'form-control',

                )))
            ->add('Sauvegarder',SubmitType::class, array(
                'attr' => array(
                    'class' => 'btn btn-primary',

                )))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Settings::class,
        ]);
    }
}
