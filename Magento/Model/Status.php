<?php

class Monogo_Application_Model_Application_Status extends Mage_Core_Model_Abstract {

    /**
     * Status new
     * @var integer
     */
    const APPLICATION_NEW_STATUS_ID = 1;

    /**
     * If of status DOCUMENT COMPLETED
     * @var integer
     */
    const DOCUMENT_COMPLETED_STATUS_ID = 6;

    /**
     * Application Status enabled ID
     * @var integer
     */
    const APPLICATION_DOCUMENTS_TO_FILL_STATUS_ID = 60;

    /**
     * Application Status enabled ID
     * @var integer
     */
    const APPLICATION_ENABLED_STATUS_ID = 2;

    /**
     * Application Status forwarded to enabled ID
     * @var integer
     */
    const APPLICATION_FORWARDED_TO_ENABLED_STATUS_ID = 12;

    /**
     * Application Status resignation ID
     * @var integer
     */
    const APPLICATION_RESIGANTION_STATUS_ID = 7;

    /**
     * Application Status canceled ID
     * @var integer
     */
    const APPLICATION_CANCEL_STATUS_ID = 18;

    /**
     * Application Status positive decision ID
     * @var integer
     */
    const APPLICATION_POSITIVE_DECISION_STATUS_ID = 5;

    /**
     * Application Status negative decision ID
     * @var integer
     */
    const APPLICATION_NEGATIVE_DECISION_STATUS_ID = 4;

    /**
     * Application Status edit ID
     * @var integer
     */
    const APPLICATION_TO_EDIT_ID = 16;

    /**
     * Multi Application to improve status ID
     * @var integer
     */
    const MULTI_APPLICATION_TO_EDIT_ID = 29;

    /**
     * Multi Application to improve status ID
     * @var integer
     */
    const MULTI_APPLICATION_OFFER_FOR_CLIENT = 30;

    /**
     * Multi Application offer selected ID
     * @var integer
     */
    const MULTI_APPLICATION_OFFER_SELECTED = 31;

    /**
     * Multi Application canceled
     * @var integer
     */
    const MULTI_APPLICATION_CANCELED = 35;

    /**
     * Multi Application documents completed
     * @var integer
     */
    const MULTI_APPLICATION_DOCUMENTS_COMPLETED = 32;

    /**
     * Multi Application Status forwarded to enabled ID
     * @var integer
     */
    const MULTI_APPLICATION_FORWARDED_TO_ENABLED = 34;

    /**
     * Multi Application Status enabled ID
     * @var integer
     */
    const MULTI_APPLICATION_ENABLED = 61;

    /**
     * Application Status enabled ID
     * @var integer
     */
    const MULTI_APPLICATION_DOCUMENTS_TO_FILL_STATUS = 33;

    /**
     * Multi Application Status negative decision ID
     * @var integer
     */
    const MULTI_APPLICATION_NEGATIVE_DECISION_STATUS = 62;

    protected function _construct() {
        $this->_init('monogo_application/application_status');
    }

    public function checkDefaultStatus($type = 'application') {
        /* @var $collection Monogo_Application_Model_Resource_Application_Status_Collection */
        $collection = $this->getCollection();

        $collection->addFieldToFilter('is_default', 1);

        $collection->addFieldToFilter('type', $type);

        $collection->getSelect()
                ->order("main_table.updated DESC");

        $changedStatusRecord = null;

        if ($collection->count() > 1) {
            foreach ($collection as $row) {
                if ($this->getEntityId() !== $row->getEntityId()) {
                    $row->setIsDefault(false);
                    $row->save();
                    $changedStatusRecord = $row;
                }
            }

            if ($changedStatusRecord) {
                Mage::getSingleton('adminhtml/session')->addSuccess(
                        Mage::helper('monogo_application')->__('The application default status has been changed from %s to %s.', $this->getStatusName(), $changedStatusRecord->getStatusName())
                );
            }
        } else {
            return true;
        }
    }

    /**
     * Get default status
     */
    public function getDefaultStatus($type = 'application') {
        $collection = $this->getCollection();

        $collection->addFieldToFilter('is_default', 1);

        $collection->addFieldToFilter('type', $type);

        if ($collection->count() > 0) {
            return $collection->getFirstItem();
        } else {
            return null;
        }
    }

    /**
     * Processing object after load data
     *
     * @return Mage_Core_Model_Abstract
     */
    protected function _afterLoad() {
        Mage::dispatchEvent('model_load_after', array('object' => $this));
        Mage::dispatchEvent($this->_eventPrefix . '_load_after', $this->_getEventData());
        $data = $this->_getEventData();
        $data = $data['data_object'];
        $data->setRoles(explode(',', $data->getRoles()));
        return $this;
    }

    /**
     * if $notes is true then return array with notes
     * @param type $notes
     * @return array
     */
    public function getOptions($type = 'application', $notes = false) {
        //logged user role
        $roleId = Monogo_Application_Model_Config::getLoggedUserRoleID();
        /* @var $collection Monogo_Application_Model_Resource_Application_Status_Collection */
        $collection = $this->getCollection();

        $collection->addFieldToFilter('type', $type);

        $arr = array();

        $arr[''] = Mage::helper('monogo_application')->__('-- Select status --');
        foreach ($collection as $row) {
            $arr[$row->getId()] = $row->getStatusName();
            if ($notes) {
                $arr[$row->getId()] = $row->getNotes();
            }
        }
        return $arr;
    }

    public static function getStatusesArr($type = 'application') {
        $arr = array();
        $obj = new self;
        /* @var $collection Monogo_Application_Model_Resource_Application_Status_Collection */
        $collection = $obj->getCollection();

        $collection->addFieldToFilter('type', $type);

        $collection->getSelect()
                ->order("main_table.updated DESC");

        $collection->load();

        foreach ($collection as $row) {
            $arr[$row->getEntityId()] = $row->getStatusName();
        }

        return $arr;
    }

    public function getOptionsFilter($type = 'application', $key = 'status_code') {
        /* @var $collection Monogo_Application_Model_Resource_Application_Status_Collection */
        $collection = $this->getCollection();

        $collection->addFieldToFilter('type', $type);

        $arr = array();

        $arr[''] = Mage::helper('monogo_application')->__('-- Select status --');
        foreach ($collection as $row) {
            $arr[$row->getData($key)] = $row->getStatusName();
        }
        return $arr;
    }

    /**
     * Save status code  to new status
     * @return \Monogo_Application_Model_Application
     */
    public function _beforeSave() {
        $data = $this->getData();
        if (!isset($data['status_code']) || !$data['status_code']) {
            $this->setStatusCode(Mage::getModel('catalog/product_url')->formatUrlKey($data['status_name']));
        }
        return parent::_beforeSave();
    }

    /**
     * @todo - change status id to status code (make changes in database)
     * @param $statusId
     */
    public function setStatus($status, $applicationId) {
        $status = Mage::getModel('monogo_application/application_status')->load($status);

        if ($status) {
            $modelStatusHistory = Mage::getModel('monogo_application/application_status_history');
            $modelStatusHistory
                    ->setData(array(
                        'status_id' => $status->getEntityId(),
                        'status_code' => $status->getStatusCode(),
                        'application_id' => $applicationId
                    ))
                    ->save();
        }
    }

    /**
     * Get start status for application
     * @param type $applicationId
     * @param type $type
     * @return type
     */
    public function getStartStatus($applicationId, $type = 'application') {
        /* @var $collection Monogo_Application_Model_Resource_Application_Status_History_Collection */
        $collection = Mage::getModel('monogo_application/application_status_history')->getCollection();

        $resource = Mage::getModel('core/resource');

        if ($type === 'application') {
            $collection->addFieldToSelect('application_id', $applicationId);
        } else if ($type === 'multi_application') {
            $collection->addFieldToSelect('application_multi_id', $applicationId);
        }

        $collection
                ->getSelect()
                ->order('created_at ASC');

        if ($collection->count() > 0) {
            $statusHistory = $collection->getFirstItem();
            if ($statusHistory->getStatusId()) {
                $status = Mage::getModel('monogo_application/application_status')->load($statusHistory->getStatusId(), 'status_id');
                if ($status->getId()) {
                    return $status;
                }
            }
        }
        return null;
    }

}
