<?php

namespace App\Form;

use App\Entity\Rencontre;
use App\Entity\Team;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RencontreType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('opponent', null, [
                'label' => 'Adversaire',
            ])
            ->add('date', DateTimeType::class, [
                'label' => 'Date du match',
                'widget' => 'single_text',
            ])
            ->add('score_team', IntegerType::class, [
                'label' => 'Score equipe',
                'required' => false,
                'attr' => ['min' => 0],
            ])
            ->add('score_opponent', IntegerType::class, [
                'label' => 'Score adversaire',
                'required' => false,
                'attr' => ['min' => 0],
            ])
            ->add('analyse', TextareaType::class, [
                'label' => 'Analyse du match',
                'required' => false,
            ])
            ->add('team', EntityType::class, [
                'label' => 'Equipe',
                'class' => Team::class,
                'choice_label' => 'nom',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Rencontre::class,
        ]);
    }
}
