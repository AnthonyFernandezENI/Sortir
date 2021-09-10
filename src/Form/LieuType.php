<?php

namespace App\Form;

use App\Entity\Lieu;
use Doctrine\DBAL\Types\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LieuType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nom', EntityType::class,
                ["label" => false ,
                    'class' => Lieu::class,
                    'choice_label'=>"nom"])
            ->add('rue')
            ->add('longitude')
            ->add('latitude')
            //->add('ville', VilleType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Lieu::class
        ]);
    }
}
