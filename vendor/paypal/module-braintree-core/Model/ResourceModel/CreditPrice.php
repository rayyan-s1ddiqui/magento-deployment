<?php
/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class CreditPrice extends AbstractDb
{
    /**
     * Resource initialization
     *
     * @return void
     */
    public function _construct(): void //@codingStandardsIgnoreLine
    {
        $this->_init('braintree_credit_prices', 'id');
    }
}
