<?php

namespace App\Form;

use App\Entity\BoatModel;
use App\Entity\RentalRate;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RentalRateFormType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $builder
            ->add('boatModel', EntityType::class, [
                'class' => BoatModel::class,
                'choice_label' => 'name',
                'label' => 'Modèle',
            ])
            ->add('label', TextType::class, [
                'label' => 'Libellé',
            ])
            ->add('durationHours', IntegerType::class, [
                'label' => 'Durée (heures)',
                'attr' => [
                    'min' => 1,
                ],
            ])
            ->add('startTime', TimeType::class, [
                'label' => 'Heure de départ',
                'input' => 'datetime_immutable',
                'widget' => 'single_text',
            ])
            ->add('price', MoneyType::class, [
                'label' => 'Prix',
                'currency' => 'EUR',
                'divisor' => 100,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RentalRate::class,
        ]);
    }
}