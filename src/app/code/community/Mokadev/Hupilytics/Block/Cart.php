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

class Mokadev_Hupilytics_Block_Cart extends Mage_Catalog_Block_Product_Abstract
{

    protected $_productCollection;

    public function getProductCollection()
    {
        if (is_null($this->_productCollection)) {

            $api = Mage::getModel('mokadev_hupilytics/api');

            $productsCount = Mage::helper('mokadev_hupilytics')->getRecommendationConfig('cart/products_count');
            $endpoint = Mage::helper('mokadev_hupilytics')->getRecommendationConfig('cart/endpoint');

            $productsInCart = array();
            $quote = Mage::getModel('checkout/cart')->getQuote();
            $cartItems = $quote->getAllVisibleItems();

            foreach ($cartItems as $cartItem) {
                /* @var Mage_Sales_Model_Quote_Item $cartItem */
                $productsInCart[] = $cartItem->getProductId();
            }
            $options = array(
                'basket' => Zend_Json::encode($productsInCart),
                'limit'  => $productsCount
            );

            $productIds = $api->getRecommendedProducts($endpoint, $options);

            if (empty($productIds)) return false;

            $collection = Mage::getResourceModel('catalog/product_collection');
            Mage::getModel('catalog/layer')->prepareProductCollection($collection);

            if (is_numeric($productIds[0])) {
                $collection->addIdFilter($productIds);
            }else{
                $collection->addFieldToFilter('sku', array('in' => $productIds));
            }

            $collection->addStoreFilter();
            $numProducts = $productsCount ? $productsCount : 4;
            $collection->setPage(1, $numProducts);

            $this->_productCollection = $collection;
        }

        return $this->_productCollection;

    }

    public function getEndpoint()
    {
        return Mage::helper('mokadev_hupilytics')->getRecommendationConfig('cart/endpoint');
    }

    /**
     * Render tracking scripts
     *
     * @return string
     */
    protected function _toHtml()
    {
        if (!$this->helper('mokadev_hupilytics')->isRecommendationEnabledOnCart()) {
            return '';
        }

        return parent::_toHtml();
    }

}