<?php

namespace App\Service;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Validator\Constraints as Assert;

class FormFieldFactory
{
    private array $pricingConfig;

    public function __construct(array $pricingConfig)
    {
        $this->pricingConfig = $pricingConfig;
    }

    /**
     * Add a choice field with standard configuration
     */
    public function addChoiceField(
        FormBuilderInterface $builder,
        string $fieldName,
        string $label,
        string $configKey,
        string $placeholder = null,
        array $additionalConstraints = []
    ): void {
        $choices = $this->createMultiplierChoices($this->pricingConfig['multipliers'][$configKey]);

        $constraints = array_merge([
            new Assert\NotBlank([
                'message' => sprintf('Please select a %s.', strtolower($label))
            ]),
            new Assert\Choice([
                'choices' => array_keys($choices),
                'message' => sprintf('Please select a valid %s.', strtolower($label))
            ])
        ], $additionalConstraints);

        $builder->add($fieldName, ChoiceType::class, [
            'label' => $label,
            'choices' => $choices,
            'placeholder' => $placeholder,
            'constraints' => $constraints,
            'attr' => [
                'class' => 'form-select'
            ]
        ]);
    }

    /**
     * Add a project type field
     */
    public function addProjectTypeField(FormBuilderInterface $builder): void
    {
        $choices = $this->createTitleChoices($this->pricingConfig['project_types']);

        $builder->add('projectType', ChoiceType::class, [
            'choices' => $choices,
            'placeholder' => 'Select a project type',
            'constraints' => [
                new Assert\NotBlank([
                    'message' => 'Please select a project type to continue.'
                ]),
                new Assert\Choice([
                    'choices' => array_keys($this->pricingConfig['project_types']),
                    'message' => 'Please select a valid project type.'
                ])
            ],
            'attr' => [
                'class' => 'form-select'
            ]
        ]);
    }

    /**
     * Add a features field
     */
    public function addFeaturesField(FormBuilderInterface $builder): void
    {
        $choices = $this->createTitleChoices($this->pricingConfig['features']);

        $builder->add('features', ChoiceType::class, [
            'label' => 'Additional Features',
            'choices' => $choices,
            'multiple' => true,
            'expanded' => true,
            'required' => false,
            'attr' => [
                'class' => 'features-checkboxes'
            ]
        ]);
    }

    /**
     * Add a bundles field
     */
    public function addBundlesField(FormBuilderInterface $builder): void
    {
        $builder->add('bundles', IntegerType::class, [
            'label' => 'Additional Bundles',
            'required' => false,
            'attr' => [
                'class' => 'form-control bundle-quantity',
                'min' => 0,
                'max' => $this->pricingConfig['bundles']['max_quantity'] ?? 50,
                'placeholder' => '0',
                'title' => $this->pricingConfig['bundles']['description'] ?? 'Each bundle adds 0.5 days'
            ],
            'constraints' => [
                new Assert\Range([
                    'min' => 0,
                    'max' => $this->pricingConfig['bundles']['max_quantity'] ?? 50,
                    'notInRangeMessage' => 'Bundle quantity must be between {{ min }} and {{ max }}'
                ])
            ]
        ]);
    }

    /**
     * Create choices with titles for display
     */
    private function createTitleChoices(array $items): array
    {
        $choices = [];
        foreach ($items as $key => $item) {
            $choices[$item['title'] ?? $key] = $key;
        }
        return $choices;
    }

    /**
     * Create choices for multiplier fields
     */
    private function createMultiplierChoices(array $multipliers): array
    {
        $choices = [];
        foreach ($multipliers as $key => $value) {
            $choices[$key] = $key;
        }
        return $choices;
    }
}
