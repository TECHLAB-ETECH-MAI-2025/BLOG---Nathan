<?php

namespace App\Form;

use App\Entity\Message;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class MessageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('contenu', TextareaType::class, [
                'label' => 'Message',
                'attr' => [
                    'rows' => 3,
                    'placeholder' => 'Tapez votre message...'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le message ne peut pas être vide'])
                ]
            ]);

        if ($options['show_destinataire']) {
            $builder->add('destinataire', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'email',
                'label' => 'Destinataire',
                'placeholder' => 'Choisir un destinataire'
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Message::class,
            'show_destinataire' => false,
        ]);
    }
}
