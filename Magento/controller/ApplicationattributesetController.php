
<?php

/**
 * ApplicationsetController
 *
 * @package Monogo_Aplication
 * @author Paweł Kaźmierczak
 * @version 1.0
 * @copyright Monogo 2015
 */
class Monogo_Application_Adminhtml_ApplicationattributesetController extends Mage_Adminhtml_Controller_Action {

    protected $_entityTypeId;

    public function preDispatch() {
        parent::preDispatch();
        $this->_entityTypeId = Mage::getModel('eav/entity')->setType(Monogo_Application_Model_Application::ENTITY)->getTypeId();
    }

    public function indexAction() {
        $this->_title(Mage::helper('monogo_application')->__('Application'))
                ->_title(Mage::helper('monogo_application')->__('Attribute Sets'))
                ->_title(Mage::helper('monogo_application')->__('Manage Attribute Sets'));

        $this->_setTypeId();

        $this->loadLayout();
        $this->_setActiveMenu('monogo_application/applicationattributeset');

        $this->_addBreadcrumb(Mage::helper('monogo_application')->__('Application'), Mage::helper('monogo_application')->__('Attribute Sets'));
        $this->_addBreadcrumb(
                Mage::helper('monogo_application')->__('Manage Attribute Sets'), Mage::helper('monogo_application')->__('Manage Attribute Sets'));

        $this->_addContent($this->getLayout()->createBlock('monogo_application/adminhtml_application_attribute_set_toolbar_main'));
        $this->_addContent($this->getLayout()->createBlock('monogo_application/adminhtml_application_attribute_set_grid'));

        $this->renderLayout();
    }

    public function editAction() {
        $this->_title(Mage::helper('monogo_application')->__('Application'))
                ->_title(Mage::helper('monogo_application')->__('Attribute Sets'))
                ->_title(Mage::helper('monogo_application')->__('Manage Attribute Sets'));


        $this->_setTypeId();
        $attributeSet = Mage::getModel('eav/entity_attribute_set')
                ->load($this->getRequest()->getParam('id'));

        if (!$attributeSet->getId()) {
            $this->_redirect('*/*/index');
            return;
        }

        $this->_title($attributeSet->getId() ? $attributeSet->getAttributeSetName() : $this->__('New Set'));

        Mage::register('current_attribute_set', $attributeSet);

        $this->loadLayout();
        $this->_setActiveMenu('monogo_application/applicationattributeset');
        $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

        $this->_addBreadcrumb(Mage::helper('monogo_application')->__('Application'), Mage::helper('monogo_application')->__('Application'));
        $this->_addBreadcrumb(
                Mage::helper('monogo_application')->__('Manage Application Sets'), Mage::helper('monogo_application')->__('Manage Application Sets'));

        $this->_addContent($this->getLayout()->createBlock('monogo_application/adminhtml_application_attribute_set_main'));

        $this->renderLayout();
    }

    public function setGridAction() {
        $this->_setTypeId();
        $this->getResponse()->setBody(
                $this->getLayout()
                        ->createBlock('adminhtml/catalog_product_attribute_set_grid')
                        ->toHtml());
    }

    /**
     * Save attribute set action
     *
     * [POST] Create attribute set from another set and redirect to edit page
     * [AJAX] Save attribute set data
     *
     */
    public function saveAction() {
        $entityTypeId = $this->_getEntityTypeId();
        $hasError = false;
        $attributeSetId = $this->getRequest()->getParam('id', false);
        $isNewSet = $attributeSetId == false;

        /* @var $model Mage_Eav_Model_Entity_Attribute_Set */
        $model = Mage::getModel('eav/entity_attribute_set')
                ->setEntityTypeId($entityTypeId);

        /** @var $helper Mage_Adminhtml_Helper_Data */
        $helper = Mage::helper('adminhtml');

        try {

            if ($isNewSet) {
                //filter html tags
                $name = $helper->stripTags($this->getRequest()->getParam('attribute_set_name'));
                $symbol = strtoupper($helper->stripTags($this->getRequest()->getParam('attribute_set_symbol')));

                $uniqueSymbol = $this->checkUniqueSymbol($symbol);

                $model->setAttributeSetName(trim($name));
                $model->setAttributeSetSymbol(trim($symbol));

                $commision = $this->getRequest()->getParam('attribute_set_commission');
                if ($commision) {
                    $model->setData('attribute_set_commission', $commision);
                }
            } else {
                if ($attributeSetId) {
                    $model->load($attributeSetId);
                }
                if (!$model->getId()) {
                    Mage::throwException(Mage::helper('monogo_application')->__('This attribute set no longer exists.'));
                }
                $data = Mage::helper('core')->jsonDecode($this->getRequest()->getPost('data'));

                //filter html tags
                $data['attribute_set_name'] = $helper->stripTags($data['attribute_set_name']);
                $data['attribute_set_symbol'] = strtoupper($helper->stripTags($data['attribute_set_symbol']));

                $this->checkUniqueSymbol($data['attribute_set_symbol'], $attributeSetId);

                $model->setAttributeSetSymbol(trim($data['attribute_set_symbol']));

                $commision = $data['attribute_set_commission'];
                if ($commision) {
                    $model->setData('attribute_set_commission', $commision);
                }

                $model->organizeData($data);
            }

            $model->validate();
            if ($isNewSet) {
                $model->save();
                $model->initFromSkeleton($this->getRequest()->getParam('skeleton_set'));
            }
            $model->save();
            $this->_getSession()->addSuccess(Mage::helper('monogo_application')->__('The attribute set has been saved.'));
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
            $hasError = true;
        } catch (Exception $e) {
            print_r($e->getMessage()); die;
            $this->_getSession()->addException($e, Mage::helper('monogo_application')->__('An error occurred while saving the attribute set.'));
            $hasError = true;
        }

        if ($isNewSet) {
            if ($hasError) {
                $this->_redirect('*/*/add');
            } else {
                $this->_redirect('*/*/edit', array('id' => $model->getId()));
            }
        } else {
            $response = array();
            if ($hasError) {
                $this->_initLayoutMessages('adminhtml/session');
                $response['error'] = 1;
                $response['message'] = $this->getLayout()->getMessagesBlock()->getGroupedHtml();
            } else {
                $response['error'] = 0;
                $response['url'] = $this->getUrl('*/*/');
            }
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
        }
    }

    private function checkUniqueSymbol($symbol, $id = null) {
        $rows = Mage::getModel('eav/entity_attribute_set')->getCollection()
                ->addFieldToFilter('main_table.attribute_set_symbol', $symbol);

        if ($id) {
            $rows->addFieldToFilter('main_table.attribute_set_id', array('neq' => $id));
        }

        if ($rows->count() === 0) {
            return true;
        } else {
            $this->_getSession()->addError(Mage::helper('monogo_application')->__('Symbol is used, must be unique!'));
            if ($this->getRequest()->isXmlHttpRequest()) {
                $this->_initLayoutMessages('adminhtml/session');
                $response = array();
                $response['error'] = 1;
                $response['message'] = $this->getLayout()->getMessagesBlock()->getGroupedHtml();
                $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
                $this->getResponse()->sendResponse();
                exit;
            } else {
                $this->_getSession()->setFormData($this->getRequest()->getParams());
                $this->getResponse()->setRedirect($this->getUrl('*/*/add'));
                $this->getResponse()->sendResponse();
                exit;
            }
        }
    }

    public function addAction() {
        $this->_title($this->__('Application'))
                ->_title($this->__('Attributes'))
                ->_title($this->__('Manage Attribute Sets'))
                ->_title($this->__('New Set'));

        $this->_setTypeId();

        $this->loadLayout();
        $this->_setActiveMenu('monogo_application/applicationattributeset');

        $this->_addContent($this->getLayout()->createBlock('monogo_application/adminhtml_application_attribute_set_toolbar_add'));

        $this->renderLayout();
    }

    public function deleteAction() {
        $setId = $this->getRequest()->getParam('id');
        try {
            Mage::getModel('eav/entity_attribute_set')
                    ->setId($setId)
                    ->delete();

            $this->_getSession()->addSuccess($this->__('The attribute set has been removed.'));
            $this->getResponse()->setRedirect($this->getUrl('*/*/'));
        } catch (Exception $e) {
            $this->_getSession()->addError($this->__('An error occurred while deleting this set.'));
            $this->_redirectReferer();
        }
    }

    /**
     * Define in register catalog_product entity type code as entityType
     *
     */
    protected function _setTypeId() {
        Mage::register('entityType', $this->_entityTypeId);
    }

    /**
     * Retrieve catalog product entity type id
     *
     * @return int
     */
    protected function _getEntityTypeId() {
        if (is_null(Mage::registry('entityType'))) {
            $this->_setTypeId();
        }
        return Mage::registry('entityType');
    }

}
