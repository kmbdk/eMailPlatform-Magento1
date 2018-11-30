<?php

class EP_Emailplatform_Model_Emailplatform extends Varien_Object {

    protected $_emailplatformfields;
    protected $_emailplatformlists;

    public function getXMLGeneralConfig($field) {
        return Mage::getStoreConfig('emailplatform/general/' . $field);
    }

    public function EmailplatformAvailable() {

        $requestUri = Mage::app()->getRequest()->getRequestUri();

        if ($this->getXMLGeneralConfig('active') == true && $this->getXMLGeneralConfig('username') != '' && $this->getXMLGeneralConfig('token') != '' && $this->getXMLGeneralConfig('listid') != '' && (strstr($requestUri, 'newsletter/') || strstr($requestUri, 'newsletter_subscriber/') || strstr($requestUri, 'customer/') || strstr($requestUri, 'eMailPlatform/index') || strstr($requestUri, 'checkout/onepage/'))) {
            return true;
        }

        if (Mage::app()->getStore()->getId() == 0) {
            if ($this->getXMLGeneralConfig('active') != true)
                Mage::getSingleton('adminhtml/session')->addError('eMailPlatform Configuration Error: eMailPlatform is innactive');
            if ($this->getXMLGeneralConfig('username') == '')
                Mage::getSingleton('adminhtml/session')->addError('eMailPlatform Configuration Error: API Username field is empty');
            if ($this->getXMLGeneralConfig('token') == '')
                Mage::getSingleton('adminhtml/session')->addError('eMailPlatform Configuration Error: API Token field is empty');
            if ($this->getXMLGeneralConfig('listid') == '')
                Mage::getSingleton('adminhtml/session')->addError('eMailPlatform Configuration Error: eMailPlatform list field is empty');
        }

        return false;
    }

    private function getCustomerByEmail($email) {
        
        if ($email instanceof Mage_Customer_Model_Customer) {

            $customer = $email;

            return $customer;
        }

        $collection = Mage::getResourceModel('newsletter/subscriber_collection');
        $collection->showCustomerInfo(true)
                ->addSubscriberTypeField()
                ->showStoreInfo()
                ->addFieldToFilter('subscriber_email', $email)
        ;

        return $collection->getFirstItem();
    }

    private function getListIdByStoreId($storeId) {
        $store = Mage::getModel('core/store')->load($storeId);
        $list_id = $store->getConfig('emailplatform/general/listid');

        return $list_id;
    }

    public function subscribe($email, $mobile = false, $firstname = '', $lastname = '') {

        if (!$this->EmailplatformAvailable()) {
            return;
        }

        $customer = $this->getCustomerByEmail($email);
        $customerOldMail = $this->getCustomerOldEmail();

        if ($email instanceof Mage_Customer_Model_Customer) {
            $email = $customer->getEmail();
        } 

        $api_url = Mage::getStoreConfig('emailplatform/general/url');
        $endpoint = '/Subscribers/AddSubscriberToList';
        $url = $api_url . $endpoint;
        $listid = Mage::getStoreConfig('emailplatform/general/listid');
        $confirm = Mage::getStoreConfig('emailplatform/subscribe/double_optin');
        $add_to_autoresponders = Mage::getStoreConfig('emailplatform/subscribe/add_to_autoresponders');

        $subscribe_mobile = Mage::getStoreConfig('emailplatform/subscribe/add_mobile');
        $mobile_prefix = Mage::getStoreConfig('emailplatform/subscribe/mobile_prefix');

        $firstname_fieldid = Mage::getStoreConfig('emailplatform/subscribe/firstname_fieldid');
        $lastname_fieldid = Mage::getStoreConfig('emailplatform/subscribe/lastname_fieldid');

        try {

            $contactFields = array();

            if ($firstname_fieldid != 0) {
                $contactFields[] = array(
                    'fieldid' => $firstname_fieldid,
                    'value' => $firstname
                );
            }
            if ($lastname_fieldid != 0) {
                $contactFields[] = array(
                    'fieldid' => $lastname_fieldid,
                    'value' => $lastname
                );
            }
            
            if($subscribe_mobile == 0 || $mobile_prefix == 0){
                $mobile = false;
                $mobile_prefix = false;
            }

            $params = array(
                'listid' => $listid,
                'emailaddress' => $email,
                'mobile' => $mobile,
                'mobilePrefix' => $mobile_prefix,
                'contactFields' => $contactFields,
                'add_to_autoresponders' => $add_to_autoresponders,
                'skip_listcheck' => false,
                'confirmed' => $confirm
            );

            $result = $this->MakePostRequest($url, $params);
            $log_params = serialize($params);
            
            if (!is_int($result)) {
                Mage::log($result, null, 'emailplatform_subscription.log', true);
            } else {
                Mage::log($result . " - " . $email . " Was successful added", null, 'emailplatform_subscription.log', true);
                Mage::log($email.' - '.$log_params, null, 'emailplatform_params.log', true);
            }
        } catch (exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
    }

    public function unsubscribe($email) {
        
        if (!$this->EmailplatformAvailable()) {
            return;
        }

        try {

            $api_url = Mage::getStoreConfig('emailplatform/general/url');
            $endpoint = '/Subscribers/UnsubscribeSubscriberEmail';
            $url = $api_url . $endpoint;
            $listid = Mage::getStoreConfig('emailplatform/general/listid');

            $params = array(
                'listid' => $listid,
                'emailaddress' => $email->getEmail(),
                'subscriberid' => false,
                'skipcheck' => false,
                'statid' => false
            );

            $result = $this->MakePostRequest($url, $params);

            if ($result === false) {
                Mage::getSingleton('customer/session')->addSuccess("eMailPlatform - Unsubscribe - " . $email->getEmail() . " OK");
            } else {
                Mage::getSingleton('customer/session')->addError("eMailPlatform - Error Performing the Request");
            }

            /**
             * Submit to Mailplatform
             */
        } catch (exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
    }

    public function getCustomerOldEmail() {
        return Mage::getSingleton('core/session', array('name' => 'frontend'))->getData('customer_old_email');
    }

    public function GetEmailplatformLists() {

        if (!empty($this->_emailplatformlists)) {
            return $this->_emailplatformlists;
        }

        $api_url = Mage::getStoreConfig('emailplatform/general/url');
        $endpoint = '/Users/GetLists';
        $url = $api_url . $endpoint;
        $params = array();

        $this->_emailplatformlists = $this->MakeGetRequest($url, $params);

        return $this->_emailplatformlists;
    }

    public function GetEmailplatformFields() {

        if (!empty($this->_emailplatformfields)) {
            return $this->_emailplatformfields;
        }

        $api_url = Mage::getStoreConfig('emailplatform/general/url');
        $endpoint = '/Lists/GetCustomFields';
        $listid = Mage::getStoreConfig('emailplatform/general/listid');
        $url = $api_url . $endpoint;
        $params = array(
            'listids' => $listid
        );

        $this->_emailplatformfields = $this->MakeGetRequest($url, $params);

        return $this->_emailplatformfields;
    }

    public function CheckApiCredentials() {

        $api_url = Mage::getStoreConfig('emailplatform/general/url');
        $endpoint = '/Test/TestUserToken';
        $url = $api_url . $endpoint;

        return $this->MakePostRequest($url);
    }

    private function GetHTTPHeader() {
        $username = Mage::getStoreConfig('emailplatform/general/username');
        $token = Mage::getStoreConfig('emailplatform/general/token');
        return array(
            "Accept: application/json; charset=utf-8",
            "ApiUsername: " . trim($username),
            "ApiToken: " . trim($token)
        );
    }

    private function DecodeResult($input = '') {
        return json_decode($input, TRUE);
    }

    public function MakeGetRequest($url = "", $fields = array()) {
        // open connection
        $ch = curl_init();
        if (!empty($fields)) {
            $url .= "?" . http_build_query($fields, '', '&');
        }
        // set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->GetHTTPHeader());
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        // disable for security
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

        // execute post
        $result = curl_exec($ch);

        // close connection
        curl_close($ch);
        // return $result;
        return $this->DecodeResult($result);
    }

    public function MakePostRequest($url = "", $fields = array()) {
        try {
            // open connection
            $ch = curl_init();

            // add the setting to the fields
            // $data = array_merge($fields, $this->settings);
            $encodedData = http_build_query($fields, '', '&');

            // set the url, number of POST vars, POST data
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->GetHTTPHeader());
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_POST, count($fields));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $encodedData);
            // disable for security
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

            // execute post
            $result = curl_exec($ch);

            // close connection
            curl_close($ch);
            return $this->DecodeResult($result);
        } catch (Exception $error) {
            return $error->GetMessage();
        }
    }

}
