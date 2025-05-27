<?php

namespace App\Form;

use App\Entity\ConversionDevise;
use App\Service\ConvertisseurDeviseService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConversionDeviseType extends AbstractType
{
    private ConvertisseurDeviseService $convertisseurService;

    public function __construct(ConvertisseurDeviseService $convertisseurService)
    {
        $this->convertisseurService = $convertisseurService;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $devises = $this->convertisseurService->getDevisesSupportees();
        
        // Séparer les devises Visa des autres
        $devisesVisa = [];
        $autresDevises = [];
        
        foreach ($devises as $code => $nom) {
            if ($this->convertisseurService->isDeviseVisa($code)) {
                $devisesVisa[$code] = $nom . ' ✓';
            } else {
                $autresDevises[$code] = $nom . ' (Taux fictif)';
            }
        }

        $choixDevises = [
            'Devises Visa (Taux réels)' => $devisesVisa,
            'Autres devises (Taux fictifs)' => $autresDevises
        ];

        $builder
            ->add('montant', NumberType::class, [
                'label' => 'Montant à convertir',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: 20000',
                    'min' => 0.01,
                    'step' => 0.01
                ],
                'help' => 'Montant en devise source'
            ])
            ->add('deviseSource', ChoiceType::class, [
                'label' => 'Devise source',
                'choices' => $choixDevises,
                'attr' => ['class' => 'form-select'],
                'help' => 'Devise que vous possédez'
            ])
            ->add('deviseCible', ChoiceType::class, [
                'label' => 'Devise cible',
                'choices' => $choixDevises,
                'attr' => ['class' => 'form-select'],
                'help' => 'Devise vers laquelle convertir'
            ])
            ->add('dateTransaction', DateType::class, [
                'label' => 'Date de transaction',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control',
                    'max' => (new \DateTime())->format('Y-m-d')
                ],
                'data' => new \DateTime(),
                'help' => 'Date de la conversion (ne peut pas être dans le futur)'
            ])
            ->add('fraisBancaires', NumberType::class, [
                'label' => 'Frais bancaires (%)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: 2.5',
                    'min' => 0,
                    'max' => 100,
                    'step' => 0.1
                ],
                'help' => 'Frais appliqués par votre banque (optionnel)'
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Convertir avec Visa',
                'attr' => ['class' => 'btn btn-primary btn-lg w-100']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ConversionDevise::class,
        ]);
    }
}
