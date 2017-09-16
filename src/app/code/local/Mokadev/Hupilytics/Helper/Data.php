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

class Mokadev_Hupilytics_Helper_Data extends Mage_Core_Helper_Abstract
{

    /**
     * Paths to hupilytics configuration
     */
    const XML_HUPILYTICS_TRACKING_ENABLED = 'mokadev_hupilytics/general/enabled';
    const XML_HUPILYTICS_ACCOUNT = 'mokadev_hupilytics/general/account';
    const XML_HUPILYTICS_SITE_ID = 'mokadev_hupilytics/general/site_id';
    const XML_HUPILYTICS_API_URL = 'mokadev_hupilytics/api/url';
    const XML_HUPILYTICS_API_TOKEN = 'mokadev_hupilytics/api/token';
    const XML_HUPILYTICS_RECOMMENDATION = 'mokadev_hupilytics/recommendation_';

    /**
     * Get hupilytics account ID from configuration
     *
     * @return string
     */
    public function getAccount() {
        return Mage::getStoreConfig(self::XML_HUPILYTICS_ACCOUNT);
    }

    /**
     * Get hupilytics site ID from configuration
     *
     * @return int
     */
    public function getSiteId() {
        return Mage::getStoreConfig(self::XML_HUPILYTICS_SITE_ID);
    }

    public function isEnabled() {
        return $this->getAccount() && $this->getSiteId();
    }
    /**
     * Check if hupilytics tracking is enabled
     *
     * @return boolean
     */
    public function isTrackingEnabled() {
        return $this->isEnabled() && Mage::getStoreConfigFlag(self::XML_HUPILYTICS_TRACKING_ENABLED);
    }

    /**
     * Check if hupilytics recommendation is enabled on a cart page
     *
     * @return boolean
     */
    public function isRecommendationEnabledOnCart() {
        return $this->isEnabled()
            && $this->getApiToken()
            && $this->getRecommendationConfig('cart/enabled')
            && $this->getRecommendationConfig('cart/endpoint');
    }

    /**
     * Check if hupilytics recommendation is enabled on a category page
     *
     * @return boolean
     */
    public function isRecommendationEnabledOnCategory() {
        return $this->isEnabled()
            && $this->getApiToken()
            && $this->getRecommendationConfig('category/enabled')
            && $this->getRecommendationConfig('category/endpoint');
    }

    /**
     * Check if hupilytics recommendation is enabled on a product page
     *
     * @return boolean
     */
    public function isRecommendationEnabledOnProductPage() {
        return $this->isEnabled()
            && $this->getApiToken()
            && $this->getRecommendationConfig('product/enabled')
            && $this->getRecommendationConfig('product/endpoint');
    }

    /**
     * Check if hupilytics recommendation is enabled on a home page
     *
     * @return boolean
     */
    public function isRecommendationEnabledOnHome() {
        return $this->isEnabled()
            && $this->getApiToken()
            && $this->getRecommendationConfig('home/enabled')
            && $this->getRecommendationConfig('home/endpoint');
    }

    public function getRecommendationConfig($key)
    {
        return Mage::getStoreConfig(self::XML_HUPILYTICS_RECOMMENDATION.$key);
    }

    public function getApiUrl()
    {
        $apiUrl = rtrim(Mage::getStoreConfig(self::XML_HUPILYTICS_API_URL), '/');

        return $apiUrl.'/'.$this->getAccount();
    }

    public function getApiToken()
    {
        return Mage::getStoreConfig(self::XML_HUPILYTICS_API_TOKEN);
    }
}