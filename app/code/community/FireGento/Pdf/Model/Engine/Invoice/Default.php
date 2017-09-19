<?php
/**
 * This file is part of the FIREGENTO project.
 *
 * FireGento_GermanSetup is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License version 3 as
 * published by the Free Software Foundation.
 *
 * This script is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * PHP version 5
 *
 * @category  FireGento
 * @package   FireGento_Pdf
 * @author    FireGento Team <team@firegento.com>
 * @copyright 2013 FireGento Team (http://www.firegento.de). All rights served.
 * @license   http://opensource.org/licenses/gpl-3.0 GNU General Public License, version 3 (GPLv3)
 * @version   $Id:$
 * @since     0.1.0
 */
/**
 * Default invoice rendering engine.
 *
 * @category  FireGento
 * @package   FireGento_Pdf
 * @author    FireGento Team <team@firegento.com>
 * @copyright 2013 FireGento Team (http://www.firegento.de). All rights served.
 * @license   http://opensource.org/licenses/gpl-3.0 GNU General Public License, version 3 (GPLv3)
 * @version   $Id:$
 * @since     0.1.0
 */
class FireGento_Pdf_Model_Engine_Invoice_Default extends FireGento_Pdf_Model_Engine_Abstract
{

    public function __construct()
    {
        parent::__construct();
        $this->setMode('invoice');
    }

    /**
     * Return PDF document
     *
     * @param  array $invoices
     * @return Zend_Pdf
     */
    public function getPdf($invoices = array())
    {
        $this->_beforeGetPdf();
        $this->_initRenderer('invoice');

        $pdf = new Zend_Pdf();
        $this->_setPdf($pdf);

        $style = new Zend_Pdf_Style();
        $this->_setFontBold($style, 10);

        // pagecounter is 0 at the beginning, because it is incremented in newPage()
        $this->pagecounter = 0;

        $originalStore = Mage::app()->getStore();
        $emulatedStore = false;

        foreach ($invoices as $invoice) {
            if ($invoice->getStoreId()) {
                Mage::app()->getLocale()->emulate($invoice->getStoreId());
                Mage::app()->setCurrentStore($invoice->getStoreId());

                $emulatedStore = true;
            }
            $page = $this->newPage();

            $order = $invoice->getOrder();

            /* add logo */
            $this->insertLogo($page, $invoice->getStore());

            /* add billing address */
            $showShipping = Mage::getStoreConfig('sales_pdf/invoice/show_shipping_address');
            $this->y = 692;
            $this->insertBillingAddress($page, $order);

            /* Add shipping address */
            if ($showShipping) {
                $this->y = 780;
                $this->insertShippingAddress($page, $order);
            }

            // Add sender address
            $this->y = 705;
            $this->_insertSenderAddessBar($page);

            /* add header */
            $this->y = 592;
            $this->insertHeader($page, $order, $invoice);

            /* add table header */
            // make sure that item table does not overlap heading
            if ($this->y > 575) {
                $this->y = 575;
            }
            $this->_setFontRegular($page, 9);
            $this->insertTableHeader($page);

            $this->y -= 20;

            $position = 0;

            foreach ($invoice->getAllItems() as $item) {
                if ($item->getOrderItem()->getParentItem()) {
                    continue;
                }

                if ($this->y < 50 || (Mage::getStoreConfig('sales_pdf/firegento_pdf/show_footer') == 1 && $this->y < 100)) {
                    $page = $this->newPage(array());
                }

                $position++;
                $page = $this->_drawItem($item, $page, $order, $position);
            }

            /* add line after items */
            $page->drawLine($this->margin['left'], $this->y + 5, $this->margin['right'], $this->y + 5);

            /* add totals */
            $page = $this->insertTotals($page, $invoice);

            /* add note */
            $page = $this->_insertNote($page, $order, $invoice);

            // Add footer
            $this->_addFooter($page, $invoice->getStore());
        }

        $this->_afterGetPdf();

        if ($emulatedStore === true) {
            Mage::app()->getLocale()->revert();
            Mage::app()->setCurrentStore($originalStore);
        }

        return $pdf;
    }

    /**
     * Insert Table Header for Items
     *
     * @param Zend_Pdf_Page $page  Current Page Object of Zend_PDF
     *
     * @return void
     */
    protected function insertTableHeader(&$page)
    {
        $page->setFillColor($this->colors['grey1']);
        $page->setLineColor($this->colors['grey1']);
        $page->setLineWidth(1);
        $page->drawRectangle($this->margin['left'], $this->y, $this->margin['right'], $this->y - 15);

        $page->setFillColor($this->colors['black']);
        $font = $this->_setFontRegular($page, 9);

        $this->y -= 11;
        $page->drawText(Mage::helper('firegento_pdf')->__('No.'), $this->margin['left'] + 3, $this->y, $this->encoding);
        $page->drawText(Mage::helper('firegento_pdf')->__('Description'), $this->margin['left'] + 108, $this->y, $this->encoding);

        $columns = array();
        $columns['price'] = array(
            'label'  => Mage::helper('firegento_pdf')->__('Price'),
            '_width' => 70
        );
        $columns['price_incl_tax'] = array(
            'label'  => Mage::helper('firegento_pdf')->__('Price (incl. tax)'),
            '_width' => 70
        );
        $columns['qty'] = array(
            'label'  => Mage::helper('firegento_pdf')->__('Qty'),
            '_width' => 20
        );
        $columns['tax'] = array(
            'label'  => Mage::helper('firegento_pdf')->__('Tax'),
            '_width' => 50
        );
        $columns['tax_rate'] = array(
            'label'  => Mage::helper('firegento_pdf')->__('Tax'),
            '_width' => 50
        );
        $columns['subtotal'] = array(
            'label'  => Mage::helper('firegento_pdf')->__('Total'),
            '_width' => 80
        );
        $columns['subtotal_incl_tax'] = array(
            'label'  => Mage::helper('firegento_pdf')->__('Total (incl. tax)'),
            '_width' => 80
        );
        // draw price, tax, and subtotal in specified order
        $columnsOrder = explode(',', Mage::getStoreConfig('sales_pdf/invoice/item_price_column_order'));
        // draw starting from right
        $columnsOrder = array_reverse($columnsOrder);
        $columnOffset = 0;
        foreach ($columnsOrder as $columnName) {
            $columnName = trim($columnName);
            if (array_key_exists($columnName, $columns)) {
                $column = $columns[$columnName];
                $labelWidth = $this->widthForStringUsingFontSize($column['label'], $font, 9);
                $page->drawText(
                    $column['label'],
                    $this->margin['right'] - $columnOffset - $labelWidth,
                    $this->y,
                    $this->encoding
                );
                $columnOffset += $column['_width'];
            }
        }
    }

    /**
     * Initialize renderer process.
     *
     * @param string $type
     * @return void
     */
    protected function _initRenderer($type)
    {
        parent::_initRenderer($type);

        $this->_renderers['default'] = array(
            'model' => 'firegento_pdf/items_default',
            'renderer' => null
        );
        $this->_renderers['grouped'] = array(
            'model' => 'firegento_pdf/items_grouped',
            'renderer' => null
        );
        $this->_renderers['bundle'] = array(
            'model' => 'firegento_pdf/items_bundle',
            'renderer' => null
        );
    }

}
