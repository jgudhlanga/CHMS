<?php
/**
 * Created by PhpStorm.
 * User: jimlink
 * Date: 12/6/2015
 * Time: 5:11 PM
 */
class ControlAcl extends Zend_Controller_Plugin_Abstract
{

   public function preDispatch(Zend_Controller_Request_Abstract $request)
   {
      $controllerName  = $request->getControllerName();
      $actionName      = $request->getActionName();
     //The below mechanism it check if a resource exists in the admin resources and then check the access rights
      //if the resource exists it then  checks user rights ie a user is allowed to view the page
      //if the resource does not exists the default error structure will manage this
     if ($controllerName !== 'login'  && $controllerName !== 'error')
      {
         if (((Control::resourceExists($controllerName) && (!Control::hasAccess($controllerName) && !Control::hasAccess($controllerName, $actionName))) || ((Control::resourceExists($controllerName, $actionName)) &&(!Control::hasAccess($controllerName, $actionName)))))
         {
            //If the user has no access we send him elsewhere by changing the request
            $request->setModuleName('error')
               ->setControllerName('error')
               ->setActionName('access-error');
         }
      }
   }
}