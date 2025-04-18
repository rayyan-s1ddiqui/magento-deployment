<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Model\Validator;

use Magento\Framework\Validation\ValidationResult;
use PayPal\Braintree\Api\Data\PaymentInterface;

interface PaymentValidatorInterface
{
    /**
     * Validate PaymentData.
     *
     * @param PaymentInterface $payment
     * @return ValidationResult
     */
    public function validate(PaymentInterface $payment): ValidationResult;
}
