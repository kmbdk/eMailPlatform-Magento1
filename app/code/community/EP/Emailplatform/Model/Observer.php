<?php

class EP_Emailplatform_Model_Observer {
    
    protected function getmodeloptions() {
        return Mage::getModel('emailplatform/emailplatform');
    }

    public function addCheckbox($observer) {

        $active = $confirm = Mage::getStoreConfig('emailplatform/checkoutnewslettersubscription/active');
        $checked_as_default = Mage::getStoreConfig('emailplatform/checkoutnewslettersubscription/defaultchecked');

        if ($checked_as_default) {
            $checked = 'checked="checked"';
        } else {
            $checked = '';
        }

        if ($active) {
            if ($observer->getBlock() instanceof Mage_Checkout_Block_Agreements && false === (boolean) (int) Mage::getStoreConfig('advanced/modules_disable_output/EP_Emailplatform')) {

                $html = $observer->getTransport()->getHtml();
                $checkboxHtml = '<li><p class="agree">'
                        . '<input id="subscribe_newsletter" name="is_subscribed" ' . $checked . ' value="1" class="checkbox" type="checkbox" />'
                        . '<label for="subscribe_newsletter">' . Mage::helper('sales')->__('Subscribe to Newsletter') . '</label>'
                        . '</p></li>';
                $html = str_replace('</ol>', $checkboxHtml . '</ol>', $html);
                $observer->getTransport()->setHtml($html);
            }
        }
        
    }

    public function subscribe($observer) {

        $active = $confirm = Mage::getStoreConfig('emailplatform/checkoutnewslettersubscription/active');
        
        if ($active) {
            $quote = $observer->getEvent()->getQuote();
            
            if ($quote->getBillingAddress() && Mage::app()->getRequest()->getParam('is_subscribed', false)) {
                
                $status = Mage::getModel('newsletter/subscriber')
                        ->setImportMode(true)
                        ->subscribe($quote->getBillingAddress()->getEmail(), $quote->getBillingAddress()->getTelephone(), $quote->getBillingAddress()->getFirstname(), $quote->getBillingAddress()->getLastname());
            
            } 
            
        }
        
    }
    
    public function OnSave($observer){
        
        $status = $this->getmodeloptions()->CheckApiCredentials();
        $listid = Mage::getStoreConfig('emailplatform/general/listid');
        $subscribe_mobile = Mage::getStoreConfig('emailplatform/subscribe/add_mobile');
        $mobile_prefix = Mage::getStoreConfig('emailplatform/subscribe/mobile_prefix');
        
        if($status !== true){
            Mage::getSingleton('adminhtml/session')->addError($this->getmodeloptions()->CheckApiCredentials());
        } else {
            
            if($listid == 0){
                Mage::getSingleton('adminhtml/session')->addError('Please select eMailPlatform list');
            }
            
        }
        
        if($subscribe_mobile && $listid != 0){
            
            $lists = $this->getmodeloptions()->GetEmailplatformLists();
            
            if($lists[$listid]['sms_prefix'] != 0){
                
                Mage::getConfig()->saveConfig('emailplatform/subscribe/mobile_prefix', $lists[$listid]['sms_prefix'], 'default', 0);
                
            } else {
                
                Mage::getConfig()->saveConfig('emailplatform/subscribe/mobile_prefix', 0, 'default', 0);
                Mage::getConfig()->saveConfig('emailplatform/subscribe/add_mobile', 0, 'default', 0);
                Mage::getSingleton('adminhtml/session')->addError('Error: Please set mobile prefix on the list in eMailPlatform');
                
            }
            
        } else {
            
            if($mobile_prefix != 0){
                Mage::getConfig()->saveConfig('emailplatform/subscribe/mobile_prefix', 0, 'default', 0);
            }
            
        }
        
    }

}
