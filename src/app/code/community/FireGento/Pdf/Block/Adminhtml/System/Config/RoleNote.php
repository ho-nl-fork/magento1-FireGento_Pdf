<?php

class FireGento_Pdf_Block_Adminhtml_System_Config_RoleNote
    extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    protected $_itemRenderer;

    public function _prepareToRender()
    {
        $this->addColumn('role_id', [
            'label' => Mage::helper('core')->__('Role'),
            'renderer' => $this->_getRenderer(),
        ]);
        $this->addColumn('note', [
            'label' => Mage::helper('core')->__('Note'),
            'style' => 'height: 5em;',
        ]);

        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('core')->__('Add Note');
    }

    /**
     * @param string $columnName
     * @return string
     * @throws Exception
     */
    protected function _renderCellTemplate($columnName)
    {
        if ($columnName == 'note') {
            $column     = $this->_columns[$columnName];
            $inputName  = $this->getElement()->getName() . '[#{_id}][' . $columnName . ']';

            return '<textarea name="'.$inputName.'" style="' . $column['style'] . '">#{' . $columnName . '}</textarea>';
        }

        return parent::_renderCellTemplate($columnName);
    }

    /**
     * @return Mage_Core_Block_Abstract
     */
    protected function _getRenderer()
    {
        if (!$this->_itemRenderer) {
            $this->_itemRenderer = $this->getLayout()->createBlock(
                'firegento_pdf/adminhtml_form_field_role', '',
                array('is_render_to_js_template' => true)
            );
        }
        return $this->_itemRenderer;
    }

    /**
     * @param Varien_Object $row
     */
    protected function _prepareArrayRow(Varien_Object $row)
    {
        $row->setData(
            'option_extra_attr_' . $this->_getRenderer()
                ->calcOptionHash($row->getData('role_id')),
            'selected="selected"'
        );
    }
}
