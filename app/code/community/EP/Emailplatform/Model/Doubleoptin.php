<?php

class EP_Emailplatform_Model_Doubleoptin
{

    public function toOptionArray()
    {
        return array(
            array(
                'value' => '1',
                'label' => Mage::helper('adminhtml')->__('No')
            ),
            array(
                'value' => '0',
                'label' => Mage::helper('adminhtml')->__('Yes')
            )
        );
    }
}