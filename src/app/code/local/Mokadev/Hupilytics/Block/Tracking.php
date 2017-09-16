<?php
/**
 * Copyright (c) 2012-2017, MOKADEV SAS
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are not permitted without the express authorization of the author
 *
 * @category    Mokadev
 * @package     Mokadev_Hupilytics
 * @author      Mohamed Kaïd (contact@mokadev.com)
 * @copyright   Copyright (c) 2012-2017 MOKADEV SAS (https://www.mokadev.com)
 * @license     Proprietary - no redistribution without authorization
 *
 **/

class Mokadev_Hupilytics_Block_Tracking extends Mage_Core_Block_Template
{
    /**
     * Tracking Orders info
     * @see http://piwik.org/docs/ecommerce-analytics/ OR https://github.com/hupi-analytics/hupilytics
     * @return string
     */
    protected function _getOrdersTrackingCode()
    {
        $orderIds = $this->getOrderIds();
        if (empty($orderIds) || !is_array($orderIds)) {
            return;
        }

        $collection = Mage::getResourceModel('sales/order_collection')
            ->addFieldToFilter('entity_id', array('in' => $orderIds));
        $result = array();

        foreach ($collection as $order) {
            foreach ($order->getAllVisibleItems() as $item) {
                /* @var Mage_Sales_Model_Order_Item */

                if ($item->getQtyOrdered()) {
                    $qty = number_format($item->getQtyOrdered(), 0, '.', '');
                } else {
                    $qty = '0';
                }
                $result[] = sprintf("_paq.push(['addEcommerceItem', '%s', '%s', '%s', %s, %s]);",
                    $this->jsQuoteEscape($item->getProductId()),
                    $this->jsQuoteEscape($item->getName()),
                    '', //category name is optionnal
                    $item->getBasePrice(),
                    $qty
                );

            }

            if ($order->getBaseGrandTotal()) {
                $subtotal = $order->getBaseGrandTotal() - $order->getBaseShippingAmount() - $order->getBaseShippingTaxAmount();
            } else {
                $subtotal = '0.00';
            }

            $result[] = sprintf("_paq.push(['trackEcommerceOrder' , '%s', %s, %s, %s, %s, %s]);",
                $order->getIncrementId(),
                $order->getBaseGrandTotal(),
                $subtotal,
                $order->getBaseTaxAmount(),
                $order->getBaseShippingAmount(),
                $order->getBaseDiscountAmount()
            );
        }

        return implode("\n", $result);
    }

    /**
     * Tracking cart info
     * @see http://piwik.org/docs/ecommerce-analytics/ OR https://github.com/hupi-analytics/hupilytics
     * @return string
     */
    protected function _getCartTrackingCode()
    {
        $productsAdded = Mage::getSingleton('checkout/session')->getProductsJustAdded(true);
        $productsDeleted = Mage::getSingleton('checkout/session')->getProductsJustDeleted(true);
        $productsUpdated = Mage::getSingleton('checkout/session')->getProductsJustUpdated(true);

        $updateCart = false;
        $result = array();

        if (!empty($productsAdded)) {
            $updateCart = true;
            foreach ($productsAdded as $product) {
                $result[] = sprintf("_paq.push(['addEcommerceItem', '%s', '%s', '%s', %s, %s]);",
                    $this->jsQuoteEscape($product['id']),
                    $this->jsQuoteEscape($product['name']),
                    '',
                    $product['price'],
                    $product['qty']
                );
                $result[] = sprintf("_paq.push(['trackEvent', 'Add to Cart', '%s', '%s']);",
                    $this->jsQuoteEscape($product['id']),
                    $product['qty']
                );
            }
        }

        if (!empty($productsDeleted)) {
            $updateCart = true;
            foreach ($productsDeleted as $product) {
                if (!empty($product)) {
                    $result[] = sprintf("_paq.push(['trackEvent', 'Remove From cart', '%s', '%s']);",
                        $this->jsQuoteEscape($product['id']),
                        $product['qty']
                    );
                }
            }
        }

        if (!empty($productsUpdated)) {
            $updateCart = true;
            foreach ($productsUpdated as $product) {
                if (!empty($product) && $product['qty'] > 0) {
                    if ($product['qty'] < $product['original_qty']) {
                        $result[] = sprintf("_paq.push(['trackEvent', 'Remove From cart', '%s', '%s']);",
                            $this->jsQuoteEscape($product['id']),
                            ($product['original_qty'] - $product['qty'])
                        );
                    } elseif ($product['qty'] > $product['original_qty']) {
                        $result[] = sprintf("_paq.push(['trackEvent', 'Add to Cart', '%s', '%s']);",
                            $this->jsQuoteEscape($product['id']),
                            ($product['qty'] - $product['original_qty'])
                        );
                    }
                }

            }

        }

        if ($updateCart) {
            $grandTotal = Mage::getModel('checkout/cart')->getQuote()->getGrandTotal();
            if ($grandTotal) {
                $result[] = sprintf("_paq.push(['trackEcommerceCartUpdate', %s]);", $grandTotal);
            }
        }

        return implode("\n", $result);
    }

    /**
     * Tracking product page views
     * @see http://piwik.org/docs/ecommerce-analytics/ OR https://github.com/hupi-analytics/hupilytics
     * @return string
     */
    protected function _getProductPageCode()
    {

        $currentProduct = Mage::registry('current_product');

        if (!($currentProduct instanceof Mage_Catalog_Model_Product)) {
            return '';
        }

        $html = sprintf("_paq.push(['setEcommerceView', '%s', '%s', [%s], %s, 1]);",
            $this->jsQuoteEscape($currentProduct->getId()),
            $this->jsQuoteEscape($currentProduct->getName()),
            implode(',', $currentProduct->getCategoryIds()),
            $currentProduct->getPrice()
        );
        //$html .= "\n";
        $html .= sprintf("_paq.push(['trackEvent', 'Product Click', 'Clic', '%s']);", $this->jsQuoteEscape($currentProduct->getId()));

        //we don't want to show
        Mage::unregister('current_category');

        return $html;
    }

    /**
     * Get HUP Account
     *
     * @return int
     */
    public function getAccount()
    {
        return $this->_getHelper()->getAccount();
    }

    /**
     * Get HUP Site id
     *
     * @return int
     */
    public function getSiteId()
    {
        return $this->_getHelper()->getSiteId();
    }

    /**
     * Get user id
     *
     * @return int
     */
    public function getUserId()
    {
        /** @var $session Mage_Customer_Model_Session */
        $session = Mage::getSingleton('customer/session');

        if ($session->isLoggedIn()) {
            /** @var $customer Mage_Customer_Model_Customer */
            $customer = $session->getCustomer();
            return $customer->getId();
        }

        return '';
    }
    /**
     * @return string
     */
    public function getCurrentLanguage()
    {
        $localeCode = explode('_', Mage::getStoreConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_LOCALE));

        return strtoupper($localeCode[0]);
    }

    /**
     * Get HUP helper object
     *
     * @return Mokadev_Hupilytics_Helper_Data
     */
    protected function _getHelper()
    {
        return $this->helper('mokadev_hupilytics');
    }


    /**
     * Render tracking scripts
     *
     * @return string
     */
    protected function _toHtml()
    {
        if (!$this->_getHelper()->isTrackingEnabled()) {
            return '';
        }

        return parent::_toHtml();
    }

}