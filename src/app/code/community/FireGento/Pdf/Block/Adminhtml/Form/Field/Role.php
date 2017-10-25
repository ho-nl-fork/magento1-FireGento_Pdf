<?php

class FireGento_Pdf_Block_Adminhtml_Form_Field_Role extends Mage_Core_Block_Html_Select
{
    /**
     * @return string
     */
    public function _toHtml()
    {
        $options = Mage::getResourceSingleton('admin/roles_collection')
            ->toOptionArray();

        foreach ($options as $option) {
            $this->addOption($option['value'], $option['label']);
        }

        return parent::_toHtml();
    }

    public function setInputName($value)
    {
        return $this->setName($value);
    }
}
