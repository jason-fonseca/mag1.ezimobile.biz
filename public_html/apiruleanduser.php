<?php
include ('app/Mage.php');
$app = Mage::app('default'); 

$roleexist = Mage::getModel('api/roles')
->load('ezimerchant','role_name');

if(!$roleexist->getId())
{
$role = Mage::getModel('api/roles')
->setName('ezimerchant')
->setRoleType('G')
->save();

Mage::getModel("api/rules")
->setRoleId($role->getId())
->setResources(array('all'))
->saveRel();
}
$userexist = Mage::getModel('api/user')
->load('ezimerchant', 'username');
if(!$userexist->getId())
{
$user = Mage::getModel('api/user');
$user->setData(array(
'username' => 'ezimerchant',
'firstname' => 'ezimerchant',
'lastname' => 'ezimerchant',
'email' => 'ezimerchant@ezimerchant.com',
'api_key' => 'ezimerchant',
'api_key_confirmation' => 'ezimerchant',
'is_active' => 1,
'user_roles' => '',
'assigned_user_role' => '',
'role_name' => '',
'roles' => array($role->getId())
));
$user->save()->load($user->getId());

$user->setRoleIds(array($role->getId()))
->setRoleUserId($user->getUserId())
->saveRelations();
}
?>