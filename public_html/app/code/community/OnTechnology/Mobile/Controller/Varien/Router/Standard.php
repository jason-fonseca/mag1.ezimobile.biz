<?php

function header_callback($ch, $header)
{
	header($header, false);	

	return strlen($header);	
}

function write_callback($ch, $data)
{
	echo $data;

	return strlen($data);
	}

class OnTechnology_Mobile_Controller_Varien_Router_Standard extends Mage_Core_Controller_Varien_Router_Standard
{

		/**
     * Match the request
     *
     * @param Zend_Controller_Request_Http $request
     * @return boolean
     */
    public function match(Zend_Controller_Request_Http $request)
    {
        //checking before even try to find out that current module
        //should use this router
        if (!$this->_beforeModuleMatch()) {
            return false;
        }
$merchantid = Mage::getStoreConfig('payment/ezimerchant/merchantid');
$apikey = Mage::getStoreConfig('payment/ezimerchant/apikey');
if((strstr($_SERVER['HTTP_USER_AGENT'],'iPhone') || strstr($_SERVER['HTTP_USER_AGENT'],'iPod') || strstr($_SERVER['HTTP_USER_AGENT'],'Mobile') || strstr($_SERVER['HTTP_USER_AGENT'],'PlayBook')) && ($merchantid && $apikey))
 {

$HostKey = $apikey;
$HostURL = 'https://secure.ezimerchant.com/'.$merchantid.'/proxy/1';

 $headers = array();
 
 foreach ($_SERVER as $k => $v)
 {
  if(substr($k, 0, 5) == "HTTP_")
  {
   $k = str_replace('_', ' ', substr($k, 5));
   $k = str_replace(' ', '-', ucwords(strtolower($k)));
 
   if($k <> "Host" && $k <> "Connection")
    array_push($headers, $k.": ".$v);
  }
 }
 
 array_push($headers, 
	"X-HostKey: ".$HostKey,
	"X-Original-Remote-Addr: ".@$_SERVER["REMOTE_ADDR"],
	"X-Original-Host: ".@$_SERVER["HTTP_HOST"],
	"X-Original-Https: ". ( !empty($_SERVER["HTTPS"])? $_SERVER['HTTPS'] :""));
 
 $ch = curl_init();

 curl_setopt($ch, CURLOPT_URL, $HostURL.$_SERVER["REQUEST_URI"]);
 curl_setopt($ch, CURLOPT_HEADER, false);
 curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
 curl_setopt($ch, CURLOPT_HEADERFUNCTION, 'header_callback');
 curl_setopt($ch, CURLOPT_WRITEFUNCTION, 'write_callback');
 curl_setopt($ch, CURLOPT_BUFFERSIZE, 65536);
 curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
 curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
 curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
 
 if($_SERVER["REQUEST_METHOD"] == "HEAD")
 {
  curl_setopt($ch, CURLOPT_NOBODY, true);
 }
 
 else if($_SERVER["REQUEST_METHOD"] == "POST")
 {
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents("php://input"));
 }

  
 $output = curl_exec($ch);
 
 if(curl_getinfo($ch, CURLINFO_HTTP_CODE)=='404')
 {
     echo file_get_contents($HostURL.$_SERVER["REQUEST_URI"]);
 }
 else echo $output;
     
 
 curl_close($ch);
 die();

  exit();
}

        $this->fetchDefault();

        $front = $this->getFront();
        $path = trim($request->getPathInfo(), '/');

        if ($path) {
            $p = explode('/', $path);
        } else {
            $p = explode('/', $this->_getDefaultPath());
        }

        // get module name
        if ($request->getModuleName()) {
            $module = $request->getModuleName();
        } else {
            if (!empty($p[0])) {
                $module = $p[0];
            } else {
                $module = $this->getFront()->getDefault('module');
                $request->setAlias(Mage_Core_Model_Url_Rewrite::REWRITE_REQUEST_PATH_ALIAS, '');
            }
        }
        if (!$module) {
            if (Mage::app()->getStore()->isAdmin()) {
                $module = 'admin';
            } else {
                return false;
            }
        }

        /**
         * Searching router args by module name from route using it as key
         */
        $modules = $this->getModuleByFrontName($module);

        if ($modules === false) {
            return false;
        }

        //checkings after we foundout that this router should be used for current module
        if (!$this->_afterModuleMatch()) {
            return false;
        }

        /**
         * Going through modules to find appropriate controller
         */
        $found = false;
        foreach ($modules as $realModule) {
            $request->setRouteName($this->getRouteByFrontName($module));

            // get controller name
            if ($request->getControllerName()) {
                $controller = $request->getControllerName();
            } else {
                if (!empty($p[1])) {
                    $controller = $p[1];
                } else {
                    $controller = $front->getDefault('controller');
                    $request->setAlias(
                        Mage_Core_Model_Url_Rewrite::REWRITE_REQUEST_PATH_ALIAS,
                        ltrim($request->getOriginalPathInfo(), '/')
                    );
                }
            }

            // get action name
            if (empty($action)) {
                if ($request->getActionName()) {
                    $action = $request->getActionName();
                } else {
                    $action = !empty($p[2]) ? $p[2] : $front->getDefault('action');
                }
            }

            //checking if this place should be secure
            $this->_checkShouldBeSecure($request, '/'.$module.'/'.$controller.'/'.$action);

            $controllerClassName = $this->_validateControllerClassName($realModule, $controller);
            if (!$controllerClassName) {
                continue;
            }

            // instantiate controller class
            $controllerInstance = Mage::getControllerInstance($controllerClassName, $request, $front->getResponse());

            if (!$controllerInstance->hasAction($action)) {
                continue;
            }

            $found = true;
            break;
        }

        /**
         * if we did not found any siutibul
         */
        if (!$found) {
            if ($this->_noRouteShouldBeApplied()) {
                $controller = 'index';
                $action = 'noroute';

                $controllerClassName = $this->_validateControllerClassName($realModule, $controller);
                if (!$controllerClassName) {
                    return false;
                }

                // instantiate controller class
                $controllerInstance = Mage::getControllerInstance($controllerClassName, $request,
                    $front->getResponse());

                if (!$controllerInstance->hasAction($action)) {
                    return false;
                }
            } else {
                return false;
            }
        }

        // set values only after all the checks are done
        $request->setModuleName($module);
        $request->setControllerName($controller);
        $request->setActionName($action);
        $request->setControllerModule($realModule);

        // set parameters from pathinfo
        for ($i = 3, $l = sizeof($p); $i < $l; $i += 2) {
            $request->setParam($p[$i], isset($p[$i+1]) ? urldecode($p[$i+1]) : '');
        }

        // dispatch action
        $request->setDispatched(true);
        $controllerInstance->dispatch($action);

        return true;
    }

}