<?php

/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

/* @var $connection Magento_Db_Adapter_Pdo_Mysql */
$connection = $installer->getConnection();

$setup = Mage::getModel('customer/entity_setup', 'core_setup');

//add pesel attribute
$setup->addAttribute('customer', 'pesel', array(
    'type' => 'varchar',
    'input' => 'text',
    'label' => 'Pesel',
    'global' => true,
    'visible' => true,
    'filterable' => true,
    'required' => true,
    'user_defined' => true,
    'default' => '',
    'visible_on_front' => true,
    'source' => null,
    'frontend_class' => 'required-entry validate-digits validate-length maximum-length-11 minimum-length-11', #validation js classes
    'unique' => true,
    'comment' => 'This is uer id pesel number'
));

$customer = Mage::getModel('customer/customer');
$attrSetId = $customer->getResource()->getEntityType()->getDefaultAttributeSetId();

$setup->addAttributeToSet('customer', $attrSetId, 'General', 'pesel');

Mage::getSingleton('eav/config')
        ->getAttribute('customer', 'pesel')
        ->setData('used_in_forms', array('adminhtml_customer', 'customer_account_create', 'customer_account_edit', 'checkout_register'))
        ->save();

$installer->endSetup();

