<?php

class EP_Emailplatform_Model_Listids extends Varien_Object {

    protected $_options;
    protected $_lists = array();

    protected function getmodeloptions() {
        return Mage::getModel('emailplatform/emailplatform');
    }

    public function toOptionArray($isMultiselect = false) {
        
        if (!$this->_options) {
            $arrresult = explode("/", Mage::app()->getRequest()->getRequestUri());

            $i = 0;
            $store = "";
            $storetoload = 0;
            foreach ($arrresult as $arr) {
                if ($arr == "store") {
                    $store = $arrresult[$i + 1];
                }
                $i ++;
            }

            $allstores = Mage::getModel('core/store')->getCollection();

            foreach ($allstores as $actualstore) {
                if ($actualstore->getCode() == $store) {
                    $storetoload = (int) $actualstore->getId();
                }
            }

            Mage::app()->setCurrentStore($storetoload);

            $result = $this->getmodeloptions()->GetEmailplatformLists();

            if (!is_array($result) OR empty($result)) {
                Mage::log($result.' or no lists in eMailPlatform', null, 'emailplatform_errors.log', true);
            } else {
                foreach ($result as $item) {
                    $this->_lists[] = array(
                        'value' => $item['listid'],
                        'label' => $item['name']
                    );
                }
            }

            $this->_options = $this->_lists;
        }

        $options = $this->_options;
        if (!$isMultiselect) {
            array_unshift($options, array(
                'value' => '',
                'label' => Mage::helper('adminhtml')->__('--Please Select--')
            ));
        }
        Mage::app()->setCurrentStore(0);
        return $options;
    }

}
