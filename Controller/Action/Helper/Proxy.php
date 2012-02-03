<?php
/**
 * @see Zend_Controller_Action_Helper_Abstract
 */
require_once 'Zend/Controller/Action/Helper/Abstract.php';

/**
 * This action helper will forward an http request that your server has received
 * to another http server passing along get, post, cookies and headers and putting 
 * into the response the raw body and the headers from the remote server.
 * 
 * Example of use:
 * //In side a controller action
 * $this->_helper->proxy->proxyTo(new Zend_Uri_Http($myDestination));
 *
 * @uses       Zend_Controller_Action_Helper_Abstract
 * @category   MyZend
 * @package    MyZend_Controller
 * @subpackage MyZend_Controller_Action_Helper
 */
class MyZend_Controller_Action_Helper_Proxy extends Zend_Controller_Action_Helper_Abstract
{
    public function proxyTo(Zend_Uri_Http $uri)
    {
        $remoteServerResponse = $this->_sendRequest($uri);
        $this->_setHttpResponseCodeToResponse($remoteServerResponse);
        $this->_setHeadersToResponse($remoteServerResponse);
        $this->_setContentToResponse($remoteServerResponse);
    }
    
    private function _sendRequest(Zend_Uri_Http $uri)
    {
        $client = new Zend_Http_Client($uri->getUri());
        
        $request = $this->getRequest();
        $client->setMethod($request->getMethod());
        
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            //stripping out Host
            foreach ($headers as $key => $value) {
                if ('host' === strtolower($key)) {
                    unset($headers[$key]);
                }
            }
            $client->setHeaders($headers);
        }
        
        //get data
        $get = $request->getQuery();
        if (!empty($get)) {
            $client->setParameterGet($get);
        }
        
        
        //post data
        $post = $request->getPost();
        //stripping out _cacheVersion_
        unset($post['_cacheVersion_']);
        
        if (!empty($post)) {
            $client->setParameterPost($post);
        }
        

        return $client->request();
    }
    
    private function _setHeadersToResponse(Zend_Http_Response $remoteServerResponse)
    {
        $response = $this->getResponse();
        
        $headers = $remoteServerResponse->getHeaders();
        foreach ($headers as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $aValue) {
                    $response->setHeader($key, $aValue);
                }
            } else {
                $response->setHeader($key, $value);
            }
        }
    }
    
    private function _setContentToResponse(Zend_Http_Response $remoteServerResponse)
    {
        $this->getResponse()->setBody($remoteServerResponse->getBody());
    }
    
    private function _setHttpResponseCodeToResponse(Zend_Http_Response $remoteServerResponse)
    {
        $this->getResponse()->setHttpResponseCode($remoteServerResponse->getStatus());
    }
}

