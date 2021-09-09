<?php

namespace App\Form;

use App\Entity\Lieu;
use App\Entity\Sortie;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;

class SortieType extends AbstractType
{
    public function __construct(Security $security)
    {
        $this->security = $security;
    }
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nom',TextType::class, ['label'=>"Nom de la sortie"])
            ->add('dateDebut', DateTimeType::class, ['label'=>"Date et heure de la sortie",'widget' => 'choice'])
            ->add('dateCloture',DateType::class,['label'=>"Date limite d'inscription", 'widget' => 'choice'])
            ->add('nbInscriptionsMax',NumberType::class, ['label'=>"Nombre de place"])
            ->add('duree',NumberType::class, ['label'=>"Durée"])
            ->add('description',TextType::class, ['label'=>"Description et infos"])
//            ->add('urlPhoto')
//            ->add('etat', EntityType::class, ["label" => "Etat" , 'class' => Etat::class, 'choice_label' => "libelle"])
//            ->add('organisateur', TextType::class ,["label"=>"Organisateur", "data"=>$this->security->getUser()->getId()])
            ->add('lieu', LieuType::class)

            ->add('Enregistrer',SubmitType::class)
            //->add('Publier',SubmitType::class);
            //->add('Annuler',SubmitType::class);
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Sortie::class,
        ]);
    }
}
