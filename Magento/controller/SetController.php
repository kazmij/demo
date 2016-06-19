<?php

/**
 * Monogo_Agency_Adminhtml_Agency_SetController
 *
 */
class Monogo_Agency_Adminhtml_Agency_SetController extends Mage_Adminhtml_Controller_Action {

    public function preDispatch() {
        parent::preDispatch();
        Mage::helper('monogo_agency')->setAllowed();
    }

    public function editAction() {
        $id = $this->getRequest()->getParam('user');
        $model = Mage::getModel('admin/user');

        # if record exist
        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->_getSession()->addError(
                        Mage::helper('monogo_agency')->__('This no longer exists.')
                );
                $this->_redirect('*/*/');
                return;
            }
        }

        $agency = Mage::getModel('monogo_agency/agency')->load($this->_getSession()->getActiveAgencyId());

        Mage::register('current_user_model', $model);
        Mage::register('current_agency_model', $agency);

        $this->loadLayout();
        $this->_setActiveMenu('monogo_agency/agency');
        $this->_addContent($this->getLayout()->createBlock('monogo_agency/adminhtml_set_edit'));

        $this->renderLayout();
    }

    /**
     * Action to save edit form
     *
     * @return mixed
     */
    public function saveAction() {
        $redirectBack = $this->getRequest()->getParam('back', false);
        $data = $this->getRequest()->getPost();


        if ($data) {

            $model = Mage::getModel('monogo_agency/agency_user_to_set');
            $connection = Mage::getSingleton('core/resource')->getConnection('core_write');

            try {

                $connection->beginTransaction();
                $insert = array();

                if (empty($data['set_id'])) {
                    
                } else {
                    foreach ($data['set_id'] as $set) {
                        $insert[] = array(
                            'user_id' => $data['user_id'],
                            'set_id' => $set
                        );
                    }
                }

                $this->_getSession()->setFormData($data);
                $model->deleteForUser($data['user_id']);

                foreach ($insert as $in) {
                    $model->unsetData();
                    $model->addData($in);
                    $model->save();
                }

                $this->_getSession()->setFormData(false);
                $this->_getSession()->addSuccess(
                        Mage::helper('monogo_agency')->__('User products have been saved.')
                );
                $connection->commit();
            } catch (Mage_Core_Exception $e) {
                $connection->rollback();
                $this->_getSession()->addError($e->getMessage());
                $redirectBack = true;
            } catch (Exception $e) {
                $connection->rollback();
                $this->_getSession()->addError(Mage::helper('monogo_agency')->__('Unable to save user products.'));
                $redirectBack = true;
                Mage::logException($e);
            }
            if ($redirectBack) {
                $this->_redirect('*/*/edit', array('user' => $data['user_id']));
                return;
            }
        }

        $this->_redirect('*/agency/edit', array('id' => $this->_getSession()->getActiveAgencyId()));
    }

    protected function _isAllowed() {
        $allowedAgencyList = Mage::getSingleton('admin/session')->isAllowed('monogo_agency/monogo_agency_list');
        $allowedProductsListCoordinator = Mage::getSingleton('admin/session')->getData('canAgencyCoordinatorsManage');
        $allowedProductsListWorker = Mage::getSingleton('admin/session')->getData('canAgencyWorkersManage');

        if ($this->getRequest()->get('type') === 'above') {
            // Logges must have permissions to agency list and above agency users list
            if ($allowedAgencyList && $allowedProductsListCoordinator) {
                $actionName = $this->getRequest()->getActionName();
                // editAction no blocked because must to show agency data
                if (in_array($actionName, array('new', 'save', 'delete'))) {
                    return $allowedProductsListCoordinator;
                }
            }
        } else {
            if ($allowedAgencyList && $allowedProductsListWorker) {
                $actionName = $this->getRequest()->getActionName();
                // editAction no blocked because must to show agency data
                if (in_array($actionName, array('new', 'save', 'delete'))) {
                    return $allowedProductsListWorker;
                }
            }
        }

        return $allowedAgencyList;
    }

}
