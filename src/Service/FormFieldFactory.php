<?php

namespace App\Service;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class FormFieldFactory
{
    /** @var array<string, mixed> */
    private array $pricingConfig;

    /** @param array<string, mixed> $pricingConfig */
    public function __construct(array $pricingConfig)
    {
        $this->pricingConfig = $pricingConfig;
    }

    /**
     * Add a choice field with standard configuration.
     */
    /** @param array<string, mixed> $additionalConstraints */
    public function addChoiceField(
        FormBuilderInterface $builder,
        string $fieldName,
        string $label,
        string $configKey,
        ?string $placeholder = null,
        array $additionalConstraints = [],
    ): void {
        $choices = $this->createMultiplierChoices($this->pricingConfig['multipliers'][$configKey]);
        dump($choices);

        $constraints = array_merge([
            new Assert\NotBlank([
                'message' => sprintf('Please select a %s.', strtolower($label)),
            ]),
            new Assert\Choice([
                'choices' => array_values($choices),
                'message' => sprintf('Please select a valid %s.', strtolower($label)),
            ]),
        ], $additionalConstraints);

        $builder->add($fieldName, ChoiceType::class, [
            'label' => $this->formatLabel($label),
            'choices' => $choices,
            'placeholder' => $placeholder,
            'constraints' => $constraints,
            'attr' => [
                'class' => 'form-select',
            ],
        ]);
    }

    /**
     * Add a project type field.
     */
    public function addProjectTypeField(FormBuilderInterface $builder): void
    {
        $choices = $this->createTitleChoices($this->pricingConfig['project_types']);

        $builder->add('projectType', ChoiceType::class, [
            'choices' => $choices,
            'placeholder' => 'Select a project type',
            'constraints' => [
                new Assert\NotBlank([
                    'message' => 'Please select a project type to continue.',
                ]),
                new Assert\Choice([
                    'choices' => array_values($choices),
                    'message' => 'Please select a valid project type.',
                ]),
            ],
            'attr' => [
                'class' => 'form-select',
            ],
        ]);
    }

    /**
     * Add a features field.
     */
    public function addFeaturesField(FormBuilderInterface $builder): void
    {
        $choices = $this->createTitleChoices($this->pricingConfig['features']);

        $builder->add('features', ChoiceType::class, [
            'label' => $this->formatLabel('Additional Features'),
            'choices' => $choices,
            'multiple' => true,
            'expanded' => true,
            'required' => false,
            'attr' => [
                'class' => 'features-checkboxes',
            ],
        ]);
    }

    /**
     * Add a bundles field.
     */
    public function addBundlesField(FormBuilderInterface $builder): void
    {
        $builder->add('bundles', IntegerType::class, [
            'label' => $this->formatLabel('Additional Bundles'),
            'required' => false,
            'attr' => [
                'class' => 'form-control bundle-quantity',
                'min' => 0,
                'max' => $this->pricingConfig['bundles']['max_quantity'] ?? 50,
                'placeholder' => '0',
                'title' => $this->pricingConfig['bundles']['description'] ?? 'Each bundle adds 0.5 days',
            ],
            'constraints' => [
                new Assert\Range([
                    'min' => 0,
                    'max' => $this->pricingConfig['bundles']['max_quantity'] ?? 50,
                    'notInRangeMessage' => 'Bundle quantity must be between {{ min }} and {{ max }}',
                ]),
            ],
        ]);
    }

    /**
     * Create choices with titles for display.
     */
    /**
     * @phpstan-param array<string, array<string, string>> $items
     *
     * @return array<string, string>
     */
    private function createTitleChoices(array $items): array
    {
        $choices = [];
        foreach ($items as $key => $item) {
            $displayText = $item['title'] ?? $key;
            $choices[$this->formatLabel($displayText)] = $key;
        }

        return $choices;
    }

    /**
     * Create choices for multiplier fields.
     */
    /**
     * @phpstan-param array<string, float> $items
     *
     * @return array<string, string>
     */
    private function createMultiplierChoices(array $items): array
    {
        $choices = [];
        foreach ($items as $key => $value) {
            // Format the label with the percentage e.g 1 = 100% 1.5 = +50% 2 = +100%
            $choices[$this->formatLabel($key) . ' (+' . (($value * 100) - 100) . '%)'] = $key;
        }

        return $choices;
    }

    /**
     * Format a label to title case and replace underscores with spaces.
     */
    private function formatLabel(string $label): string
    {
        // Replace underscores with spaces
        $label = str_replace('_', ' ', $label);

        // Convert to title case
        return ucwords(strtolower($label));
    }
}
