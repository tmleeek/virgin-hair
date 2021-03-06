<?php

/**
 * Magestore
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the Magestore.com license that is
 * available through the world-wide-web at this URL:
 * http://www.magestore.com/license-agreement.html
 * 
 * DISCLAIMER
 * 
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 * 
 * @category    Magestore
 * @package     Magestore_RewardPointsBehavior
 * @copyright   Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */

/**
 * RewardPointsBehavior Newsletter Model
 * 
 * @category    Magestore
 * @package     Magestore_RewardPointsBehavior
 * @author      Magestore Developer
 */
class Magestore_RewardPointsBehavior_Model_Actions_Poll extends Magestore_RewardPoints_Model_Action_Abstract implements Magestore_RewardPoints_Model_Action_Interface {

    /**
     * get reward point Poll label
     * 
     * @return string
     */
    public function getActionLabel() {
        return Mage::helper('rewardpointsbehavior')->__('Receive point(s) for taking polls');
    }

    /**
     * get reward point Poll type
     * 
     * @return int
     */
    public function getActionType() {
        return Magestore_RewardPoints_Model_Transaction::ACTION_TYPE_EARN;
    }

    /**
     * get reward point Poll amount
     * 
     * @return int
     */
    public function getPointAmount() {
        $store_id = Mage::app()->getStore()->getId();
        $amount = (int) Mage::helper('rewardpointsbehavior')->getPollConfig('poll', $store_id);
        $amountPerDay = (int) Mage::helper('rewardpointsbehavior')->getPollConfig('poll_limit', $store_id);
        if (is_numeric($amountPerDay) && $amountPerDay > 0) {
            $amountOfDay = Mage::helper('rewardpointsbehavior')->getAmountofDay('poll', $this->getCustomer()->getId());
            if ($amount > 0 && ($amountOfDay + $amount) > $amountPerDay) {
                if ($amountOfDay < $amountPerDay)
                    $amount = $amountPerDay - $amountOfDay;
                else
                    $amount = 0;
            }
        }
        return (int) $amount;
    }

    /**
     * get reward point Poll title
     * 
     * @return string
     */
    public function getTitle() {
        return Mage::helper('rewardpointsbehavior')->__('Receive point(s) for taking polls');
    }

    /**
     * prepare data of action to storage on transactions
     * the array that returned from function $action->getData('transaction_data')
     * will be setted to transaction model
     * 
     * @return Magestore_RewardPoints_Model_Action_Interface
     */
    public function prepareTransaction() {
        $poll = $this->getData('action_object');

        $store_id = Mage::app()->getStore()->getId();
        $extraContent = $this->getData('extra_content');
        if (isset($extraContent['notice']))
            $extraContent['notice'] = htmlspecialchars($extraContent['notice']);

        if (isset($extraContent['extra_content']) && is_array($extraContent['extra_content'])) {
            $extra_content = new Varien_Object($extraContent['extra_content']);
            $extraContent['extra_content'] = $extra_content->serialize(null, '=', '&', '');
        }
        $extraContent = new Varien_Object($extraContent);
        $transactionData = array(
            'status' => Magestore_RewardPoints_Model_Transaction::STATUS_COMPLETED,
            'store_id' => $store_id,
            'extra_content' => $extraContent->serialize(null, '=', '&', ''),
        );

        // Check if transaction need to hold
        $holdDays = (int) Mage::getStoreConfig(
                        Magestore_RewardPoints_Helper_Calculation_Earning::XML_PATH_HOLDING_DAYS, $store_id
        );
        if ($holdDays > 0) {
            $transactionData['status'] = Magestore_RewardPoints_Model_Transaction::STATUS_ON_HOLD;
        }

        // Set expire time for current transaction
        $expireDays = (int) Mage::getStoreConfig(
                        Magestore_RewardPoints_Helper_Calculation_Earning::XML_PATH_EARNING_EXPIRE, $store_id
        );
        $transactionData['expiration_date'] = $this->getExpirationDate($expireDays);
        $this->setData('transaction_data', $transactionData);
        return parent::prepareTransaction();
    }   

}