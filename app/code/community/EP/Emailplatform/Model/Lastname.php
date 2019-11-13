<?php

class EP_Emailplatform_Model_Lastname {

    protected $_options;
    protected $_fields = array();

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
            

            $emp_fields = $this->getmodeloptions()->GetEmailplatformFields();

            if (empty($emp_fields)) {
                // field is not requried
            } else {
                foreach ($emp_fields as $item) {
                    if($item['fieldtype'] == 'text'){
                        $this->_fields[] = array(
                            'value' => $item['fieldid'],
                            'label' => $item['fieldname']
                        );
                    }
                }
            }

            $this->_options = $this->_fields;
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
