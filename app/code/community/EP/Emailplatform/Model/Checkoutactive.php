<?php

class EP_Emailplatform_Model_Checkoutactive
{
    
    public function toOptionArray()
    {
        
        $options = array(
            array(
                'value' => '0',
                'label' => Mage::helper('adminhtml')->__('Disabled')
            ),
            array(
                'value' => '1',
                'label' => Mage::helper('adminhtml')->__('Enabled')
            )
        );
        
        $is_terms_active = Mage::getStoreConfigFlag('checkout/options/enable_agreements');
        $is_checkoutnewslettersubscription_active = Mage::getStoreConfig('emailplatform/checkoutnewslettersubscription/active');

        if ($is_checkoutnewslettersubscription_active AND ! $is_terms_active) {
            Mage::getSingleton('adminhtml/session')->addError('Error: Terms and Conditions must be enabled');
        }
        
        return $options;
        
    }
}