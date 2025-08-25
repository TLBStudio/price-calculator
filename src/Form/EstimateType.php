<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use App\Service\FormFieldFactory;

class EstimateType extends AbstractType
{
    private array $pricingConfig;

    public function __construct(array $pricingConfig)
    {
        $this->pricingConfig = $pricingConfig;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $fieldFactory = new FormFieldFactory($this->pricingConfig);

        // Project types
        $fieldFactory->addProjectTypeField($builder);

        // Discovery
        $fieldFactory->addChoiceField($builder, 'discovery', 'Discovery & Research', 'discovery');

        // Complexity
        $fieldFactory->addChoiceField($builder, 'complexity', 'Project Complexity', 'complexity');

        // Risk
        $fieldFactory->addChoiceField($builder, 'risk', 'Risk Assessment', 'risk');

        // Support
        $fieldFactory->addChoiceField($builder, 'support', 'Support Requirements', 'support');

        // Speed
        $fieldFactory->addChoiceField($builder, 'speed', 'Project Timeline', 'speed');

        // Compliance Requirements
        if (isset($this->pricingConfig['multipliers']['compliance'])) {
            $fieldFactory->addChoiceField($builder, 'compliance', 'Compliance Level', 'compliance');
        }

        // Real-time Requirements
        if (isset($this->pricingConfig['multipliers']['real_time'])) {
            $fieldFactory->addChoiceField($builder, 'realTime', 'Real-time Requirements', 'real_time');
        }

        // Features
        $fieldFactory->addFeaturesField($builder);

        // Bundles
        $fieldFactory->addBundlesField($builder);
    }



    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => null, // Allow array data
            'attr' => [
                'class' => 'estimate-form',
                'novalidate' => 'novalidate' // We'll handle validation with JavaScript
            ]
        ]);
    }


}
