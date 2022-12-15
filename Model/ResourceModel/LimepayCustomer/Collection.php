<?php

namespace Limepay\Lpcheckout\Model\ResourceModel\LimepayCustomer;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
  
    protected $_idFieldName = 'id';

    protected function _construct()
    {
        $this->_init('Limepay\Lpcheckout\Model\LimepayCustomer', 'Limepay\Lpcheckout\Model\ResourceModel\LimepayCustomer');
    }

    public function getByCustomerId($customerId, $pk)
    {
        $this->clear()->getSelect()->reset(\Magento\Framework\DB\Select::WHERE);

        $collection = $this->addFieldToSelect('*')
                    ->addFieldToFilter('customer_id', ['eq' => $customerId])
                    ->addFieldToFilter(['public_key', 'public_key'], [$pk, ["null" => true]])
                    ->setOrder('public_key','DESC');

        if (!$collection->getSize())
            return null;
        else
            $customer = $collection->getFirstItem();

        if (!$customer->getPublicKey())
            $customer->setPublicKey($pk)->save();

        return $customer;
    }

    public function getBySessionId($sessionId, $pk)
    {
        $this->clear()->getSelect()->reset(\Magento\Framework\DB\Select::WHERE);

        $collection = $this->addFieldToSelect('*')
                    ->addFieldToFilter('session_id', ['eq' => $sessionId])
                    ->addFieldToFilter(['public_key', 'public_key'], [$pk, ["null" => true]])
                    ->setOrder('public_key','DESC');

        if (!$collection->getSize())
            return null;
        else
            $customer = $collection->getFirstItem();

        if (!$customer->getPublicKey())
            $customer->setPublicKey($pk)->save();

        return $customer;
    }

    public function getByLimepayCustomerId($limepayCustomerId)
    {
        $this->clear()->getSelect()->reset(\Magento\Framework\DB\Select::WHERE);

        $collection = $this->addFieldToSelect('*')
                    ->addFieldToFilter('limepay_id', ['eq' => $limepayCustomerId])
                    ->setOrder('public_key','DESC');

        if (!$collection->getSize())
            return null;
        else
            $customer = $collection->getFirstItem();

        return $customer;
    }

    public function getByLimepayCustomerIdAndPk($limepayCustomerId, $pk)
    {
        $this->clear()->getSelect()->reset(\Magento\Framework\DB\Select::WHERE);

        $collection = $this->addFieldToSelect('*')
                    ->addFieldToFilter('limepay_id', ['eq' => $limepayCustomerId])
                    ->addFieldToFilter(['public_key', 'public_key'], [$pk, ["null" => true]])
                    ->setOrder('public_key','DESC');

        if (!$collection->getSize())
            return null;
        else
            $customer = $collection->getFirstItem();

        return $customer;
    }
}
