<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\IsTrue;

class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nombre',
                'constraints' => [new NotBlank(['message' => 'El nombre es obligatorio'])],
            ])
            ->add('apellidos', TextType::class, [
                'label' => 'Apellidos',
                'constraints' => [new NotBlank(['message' => 'Los apellidos son obligatorios'])],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'constraints' => [
                    new NotBlank(['message' => 'El email es obligatorio']),
                    new Email(['message' => 'El email no es válido']),
                ],
            ])
            ->add('phone', TelType::class, [
                'label' => 'Teléfono',
                'constraints' => [new NotBlank(['message' => 'El teléfono es obligatorio'])],
            ])
            ->add('subject', TextType::class, [
                'label' => 'Asunto',
                'constraints' => [new NotBlank(['message' => 'El asunto es obligatorio'])],
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Mensaje',
                'constraints' => [new NotBlank(['message' => 'El mensaje es obligatorio'])],
            ])
            ->add('privacy', CheckboxType::class, [
                'label' => 'He leído y acepto la Política de Privacidad',
                'constraints' => [
                    new IsTrue(['message' => 'Debes aceptar la política de privacidad']),
                ],
            ])
        ;
    }
}
