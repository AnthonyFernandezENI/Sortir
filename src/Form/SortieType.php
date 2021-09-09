<?php

namespace App\Form;

use App\Entity\Lieu;
use App\Entity\Sortie;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SortieType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nom')
            ->add('dateDebut', DateTimeType::class)
            ->add('duree')
            ->add('dateCloture')
            ->add('nbInscriptionsMax')
            ->add('description')
            ->add('urlPhoto')
            //->add('etat', EntityType::class, ["label" => "Etat" , 'class' => Etat::class, 'choice_label' => "libelle"])
//            ->add('organisateur')
            ->add('lieu', EntityType::class, ["label" => "Lieu" , 'class' => Lieu::class, 'choice_label' => "nom"])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Sortie::class,
        ]);
    }
}
