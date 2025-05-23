<?php

namespace App\Form;

use App\Entity\Compra;
use App\Entity\Productos;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductosType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('price')
            ->add('image')
            ->add('cantidad')
            ->add('isFavorite')
            ->add('insideCart')
            ->add('fecha', null, [
                'widget' => 'single_text',
            ])
            ->add('categoria')
            ->add('subcategoria')
            ->add('compras', EntityType::class, [
                'class' => Compra::class,
                'choice_label' => 'id',
                'multiple' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Productos::class,
        ]);
    }
}
