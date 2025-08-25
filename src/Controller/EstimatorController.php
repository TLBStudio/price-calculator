<?php

namespace App\Controller;

use App\Form\EstimateType;
use App\Service\BusinessRuleValidator;
use App\Service\PricingEngine;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class EstimatorController extends AbstractController
{
    public function __construct(
        private BusinessRuleValidator $businessRuleValidator,
    ) {
    }

    #[Route('/', name: 'estimate')]
    public function index(Request $request, PricingEngine $engine): \Symfony\Component\HttpFoundation\Response
    {
        $estimate = null;
        $inputData = null;
        $errors = [];
        $warnings = [];

        $form = $this->createForm(EstimateType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $inputData = $form->getData();

            // Get compatibility warnings (these won't block the estimate)
            $compatibilityWarnings = $this->businessRuleValidator->getCompatibilityWarnings($inputData);
            if (!empty($compatibilityWarnings)) {
                $warnings = array_merge($warnings, $compatibilityWarnings);
            }

            // Business rule validation (these are just warnings now)
            $businessWarnings = $this->businessRuleValidator->validateBusinessRules($inputData);
            if (!empty($businessWarnings)) {
                $warnings = array_merge($warnings, $businessWarnings);
            }

            // Generate estimate regardless of warnings
            try {
                $estimate = $engine->estimate($inputData);

                // Add success message
                $this->addFlash('success', 'Estimate generated successfully! Review the details below.');

                // Add warning messages if any
                if (!empty($warnings)) {
                    foreach ($warnings as $warning) {
                        if (is_array($warning) && isset($warning['message'])) {
                            $this->addFlash('warning', $warning['message']);
                        } else {
                            $this->addFlash('warning', $warning);
                        }
                    }
                }
            } catch (\Exception $e) {
                $errors[] = 'Error generating estimate: '.$e->getMessage();
                $this->addFlash('error', 'Unable to generate estimate. Please check your inputs and try again.');
            }
        } elseif ($form->isSubmitted() && !$form->isValid()) {
            // Form validation errors
            foreach ($form->getErrors(true) as $error) {
                if ($error instanceof \Symfony\Component\Form\FormError) {
                    $errors[] = $error->getMessage();
                    $this->addFlash('error', $error->getMessage());
                }
            }
        }

        return $this->render('estimate/index.html.twig', [
            'form' => $form->createView(),
            'est' => $estimate,
            'gate' => !$this->isGranted('ROLE_STAFF'),
            'input' => $inputData,
            'config' => $this->getParameter('pricing'),
            'errors' => $errors,
            'warnings' => $warnings,
        ]);
    }
}
