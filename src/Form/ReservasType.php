<?php

namespace App\Form;

use App\Entity\Reservas;
use App\Entity\Usuarios;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReservasType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('servicio')
            ->add('peluquero')
            ->add('dia', null, [
                'widget' => 'single_text',
            ])
            ->add('hora', null, [
                'widget' => 'single_text',
            ])
            ->add('usuarios', EntityType::class, [
                'class' => usuarios::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservas::class,
        ]);
    }
}
