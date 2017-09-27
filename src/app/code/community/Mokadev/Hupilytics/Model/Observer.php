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

class Mokadev_Hupilytics_Model_Observer
{
    /**
     * Order information tracking on checkout success page
     *
     * @param Varien_Event_Observer $observer
     *
     */
    public function setHupilyticsOnOrderSuccessPage(Varien_Event_Observer $observer)
    {
        if (Mage::helper('mokadev_hupilytics')->isTrackingEnabled()) {

            $orderIds = $observer->getEvent()->getOrderIds();
            if (empty($orderIds) || !is_array($orderIds)) {
                return;
            }
            $block = Mage::app()->getFrontController()->getAction()->getLayout()->getBlock('hupilytics_tracking');
            if ($block) {
                $block->setOrderIds($orderIds);
            }
        }
    }

    /**
     * enable cart tracking code after cart update
     * event: sales_quote_product_add_after
     * @param $observer
     */
    public function showCartTrackingAfterAddToCart($observer)
    {
        if (Mage::helper('mokadev_hupilytics')->isTrackingEnabled()) {
            $quoteItems = $observer->getItems();

            // in case the products are added via a quickshop functionnality we merge all the added products
            // until the customer goes on the cart page
            $data = Mage::getSingleton('checkout/session')->getProductsJustAdded();

            if (!is_array($data)) $data = array();

            $_taxHelper  = Mage::helper('tax');
            foreach ($quoteItems as $item) {
                /** @var Mage_Sales_Model_Quote_Item $item */
                if (!$item->getParentItemId()) {

                    $priceExclTax = $_taxHelper->getPrice($item->getProduct(), $item->getProduct()->getPrice(), false);

                    $data[$item->getProductId()] = array (
                        'id' => $item->getProductId(),
                        'name' => $item->getName(),
                        'qty' => $item->getQty(),
                        'price' => $priceExclTax
                    );
                }
            }

            Mage::getSingleton('checkout/session')->setProductsJustAdded($data);
        }

    }

    /**
     * enable cart tracking code after deleting item in cart
     * event: sales_quote_remove_item
     * @param $observer
     */
    public function showCartTrackingAfterDeleteFromCart($observer)
    {
        if (Mage::helper('mokadev_hupilytics')->isTrackingEnabled()) {
            $item = $observer->getQuoteItem();
            /** @var Mage_Sales_Model_Quote_Item $item */

            // in case the products are deleted via a mass update in cart we merge all the deleted products
            // until the customer return to the cart page
            $data = Mage::getSingleton('checkout/session')->getProductsJustDeleted();

            if (!is_array($data)) $data = array();

            if (!$item->getParentItemId()) {
                $data[$item->getProductId()] = array (
                    'id' => $item->getProductId(),
                    'name' => $item->getName(),
                    'qty' => $item->getQty(),
                    'price' => $item->getPrice()
                );

                Mage::getSingleton('checkout/session')->setProductsJustDeleted($data);
            }
        }

    }

    /**
     * enable cart tracking code after deleting item in cart
     * event: checkout_cart_update_items_after
     * @param $observer
     */
    public function showCartTrackingAfterCartUpdate($observer)
    {
        if (Mage::helper('mokadev_hupilytics')->isTrackingEnabled()) {
            $infos = $observer->getInfo();

            if (!empty($infos)) {
                foreach ($infos as $itemId => $info){
                    /** @var Mage_Sales_Model_Quote_Item $item */
                    $item = Mage::getModel('sales/quote_item')->load($itemId);
                    $data[] = array (
                        'id' => $item->getProductId(),
                        'original_qty' => $item->getQty(),
                        'qty' => $info['qty'],
                        'price' => $item->getPrice(),
                        'name' => $item->getName(),
                    );
                }

                Mage::getSingleton('checkout/session')->setProductsJustUpdated($data);

            }

        }

    }

}