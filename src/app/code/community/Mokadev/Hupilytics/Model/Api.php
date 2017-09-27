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

class Mokadev_Hupilytics_Model_Api extends Mokadev_Hupilytics_Model_Client
{
    protected $_client;

    /**
     *
     * @return $this Mokadev_Hupilytics_Model_Api
     */
    public function __construct()
    {
        $options = array(
            'base_url' => Mage::helper('mokadev_hupilytics')->getApiUrl(),
            'user_agent' => 'Magento-Hupilytics',
            'headers' => array(
                'Content-Type' => 'application/json',
                'Accept-Version' => 'v1',
                'X-API-Token' => $this->_getToken()
            )
        );

        parent::__construct($options);
    }

    /**
     * get products ids from Hupilytics api
     *
     * @param string $endpoint
     * @param array $options
     * @return bool|array
     */
    public function getRecommendedProducts($endpoint, $options = array())
    {
        $cookie = $this->_getPkIdCookie();

        if($cookie && $this->_getToken()) {
            $cookieData = explode('.', $cookie);
            $visitorId = array_shift($cookieData);

            $customer = Mage::getSingleton('customer/session')->getCustomer();
            $filters = array(
                'visitor_id' => $visitorId,
                'uid' => $customer->getId() ? $customer : '',
            );

            if (!empty($options)) {
                foreach ($options as $filter => $value) {
                    $filters[$filter] = $value;
                }
            }

            $postData = Zend_Json::encode(array(
                'client' => Mage::helper('mokadev_hupilytics')->getAccount(),
                'render_type' => 'cursor',
                'filters' => Zend_Json::encode($filters)
            ));

            $productIds = array();

            try {
                $request = $this->post($endpoint, $postData);

                if($request->error == '') {
                    $response = $request->decode_response();

                    $data = $response->data;

                    if(key(current($data)) == 'idRs') {
                        $productIds = current(array_values(array_map('current',$data)));
                    } else {
                        $productIds = array_values(array_map('current',$data));
                    }
                }
            } catch (Exception  $e) {
                Mage::logException($e);
                return false;
            }

            return $productIds;

        }
    }

    protected function _getPkIdCookie()
    {
        $cookies = Mage::getSingleton('core/cookie')->get();
        foreach ($cookies as $name => $content){
            if (strpos($name, 'pk_id') !== false) {
                return $content;
            }
        }

        return false;
    }

    protected function _getToken()
    {
        return Mage::helper('mokadev_hupilytics')->getApiToken();
    }
}