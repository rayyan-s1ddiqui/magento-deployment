<?php
/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Model\CustomFields;

/**
 * Interface CustomFieldInterface
 **/
interface CustomFieldInterface
{
    /**
     * API Name as defined in the Braintree Control Panel
     *
     * @return string
     */
    public function getApiName(): string;

    /**
     * Value for the field
     *
     * @param array $buildSubject When used with SubjectReader this will return information about the order
     * @see \PayPal\Braintree\Gateway\Helper\SubjectReader
     * @return mixed
     */
    public function getValue(array $buildSubject);
}
