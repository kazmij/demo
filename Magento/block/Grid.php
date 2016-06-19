<?php

class Monogo_ApplicationMultifb_Block_Adminhtml_Applicationmultifb_Grid extends Mage_Adminhtml_Block_Widget_Grid {

    public function __construct() {
        parent::__construct();
        $this->setId('grid_id');
        $this->setDefaultSort('updated_at');
        $this->setDefaultDir('desc');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    protected function _prepareCollection() {
        /* @var $collection Monogo_ApplicationMultiFb_Model_Resource_Applicationmultifb_Collection */
        $collection = Mage::getModel('monogo_applicationmultifb/applicationmultifb')->getCollection();

        $resource = Mage::getModel('core/resource');

        $applicationTable = $resource->getTableName('monogo_application/application_entity');

        $isCoordinator = null;
        $isOwner = null;


        if (Monogo_Application_Model_Config::hasRole(Monogo_Application_Model_Config::ROLE_RKS)) {
            $regions = array();
            $agencies = array();
            $collection_regions = Mage::getModel('monogo_rks/rks_user_to_set')->getCollection()
                    ->addFieldToFilter('user_id', Mage::getSingleton('admin/session')->getUser()->getId());

            foreach ($collection_regions as $item) {
                $regions[] = $item->getSetId();
            }

            $collection_agencies = Mage::getModel('monogo_agency/agency')->getCollection()
                    ->addFieldToFilter('region_id', array('in' => $regions));

            foreach ($collection_agencies as $item) {
                $agencies[] = $item->getAgencyId();
            }

            if (empty($agencies)) {
                $agencies = array(0);
            }

            $collection
                    ->getSelect()
                    ->joinLeft(array('user_under' => $resource->getTableName('monogo_agency/under')), 'user_under.agency_id IN (' . implode(',', $agencies) . ')', array())
                    ->joinLeft(array('user_above' => $resource->getTableName('monogo_agency/above')), 'user_above.agency_id IN (' . implode(',', $agencies) . ')', array())
                    ->where('user_under.user_id IS NOT NULL OR user_above.user_id IS NOT NULL');
        } else if (($isOwner = (Monogo_Application_Model_Config::hasRole(Monogo_Application_Model_Config::ROLE_OWNER))) ||
                Monogo_Application_Model_Config::hasRole(Monogo_Application_Model_Config::ROLE_TRADER) ||
                ($isCoordinator = (Monogo_Application_Model_Config::hasRole(Monogo_Application_Model_Config::ROLE_COORDINATOR)))) {
            $userAgency = Mage::getModel('monogo_agency/agency')->getAgencyForUser(Monogo_Application_Model_Config::getLoggedUser()->getId());
            $resource = Mage::getModel('core/resource');

            if ($isOwner || $isCoordinator) { //all application with logged owner agency id
                $collection
                        ->getSelect()
                        ->where('main_table.entity_id IN (SELECT app.entity_id FROM ' . $resource->getTableName('monogo_applicationmultifb/applicationmultifb') . ' app WHERE app.user_id IN ( '
                                . ' SELECT above.user_id FROM ' . $resource->getTableName('monogo_agency/above') . ' above WHERE above.agency_id = ' . ($userAgency->getId() ? $userAgency->getId() : 0) . ' '
                                . ' UNION '
                                . ' SELECT under.user_id FROM ' . $resource->getTableName('monogo_agency/under') . ' under WHERE under.agency_id = ' . ($userAgency->getId() ? $userAgency->getId() : 0) . ' '
                                . '))');
            } else {
                $collection->addFieldToFilter('main_table.user_id', Monogo_Application_Model_Config::getLoggedUser()->getId());
            }
        } else if (Monogo_Application_Model_Config::hasRole(Monogo_Application_Model_Config::ROLE_PARTNER)) {
            $partnerUser = Monogo_Application_Model_Config::getLoggedUser();
            $partnerAvailableProducts = Mage::getModel('monogo_partner/partner_user_to_set')->getSetsForUser($partnerUser->getUserId());
            $arrProducts = array();
            foreach ($partnerAvailableProducts as $product) {
                $arrProducts[] = $product->getSetId();
            }

            if (empty($arrProducts)) {
                $arrProducts = array(0);
            }

            $collection
                    ->getSelect()
                    ->join(array('application' => $resource->getTableName('monogo_application/application_entity')), 'application.entity_id = main_table.selected_id AND application.attribute_set_id IN (' . implode(',', $arrProducts) . ')', array());
        }

        $this->customerSubquery = "CONCAT((SELECT ce.value FROM customer_entity_varchar ce JOIN eav_attribute ceav ON ceav.attribute_id = ce.attribute_id WHERE ce.entity_id = customer.entity_id AND ceav.attribute_code = 'firstname'  LIMIT 1), ' ', (SELECT ce.value FROM customer_entity_varchar ce JOIN eav_attribute ceav ON ceav.attribute_id = ce.attribute_id WHERE ce.entity_id = customer.entity_id AND ceav.attribute_code = 'lastname'  LIMIT 1))";

        $collection
                ->addExpressionFieldToSelect('cnt', '(SELECT COUNT(app.entity_id) FROM ' . $applicationTable . ' app WHERE app.application_multi_id = main_table.entity_id)', array('app.entity_id'))
                ->getSelect()
                ->join(array('user' => $resource->getTableName('admin/user')), "main_table.user_id = user.user_id ", array('userName' => "CONCAT(user.firstname, ' ', user.lastname)"))
                ->join(array('customer' => $resource->getTableName('customer_entity')), "main_table.customer_id = customer.entity_id ", array('customerName' => $this->customerSubquery))
                ->join(array('status_table' => $collection->getTable('monogo_application/application_status_history')), "main_table.entity_id = status_table.application_multi_id AND status_table.entity_id = (SELECT MAX(statuses3.entity_id) FROM " . $collection->getTable('monogo_application/application_status_history') . " statuses3 WHERE statuses3.application_multi_id = main_table.entity_id) AND status_table.created_at = (SELECT MAX(statuses2.created_at) FROM " . $collection->getTable('monogo_application/application_status_history') . " statuses2 WHERE statuses2.application_multi_id = main_table.entity_id)", array())
                ->joinLeft(array('status_origin' => $collection->getTable('monogo_application/application_status')), "status_origin.entity_id = status_table.status_id ", array('status_origin.color_hex', 'status_origin.status_name'));

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns() {

        $this->addColumn(
                'entity_id', array(
            'header' => Mage::helper('monogo_applicationmultifb')->__('Id'),
            'index' => 'entity_id',
            'type' => 'number',
            'filter_condition_callback' => array($this, '_customFilter'),
                )
        );

        $this->addColumn(
                'userName', array(
            'header' => Mage::helper('monogo_applicationmultifb')->__('User who added'),
            'index' => 'userName',
            'type' => 'text',
                )
        );



        $this->addColumn(
                'customerName', array(
            'header' => Mage::helper('monogo_applicationmultifb')->__('Customer'),
            'index' => 'customerName',
            'type' => 'text',
            'filter_condition_callback' => array($this, '_customFilter'),
                )
        );

        $this->addColumn(
                'cnt', array(
            'header' => Mage::helper('monogo_applicationmultifb')->__('Applications count'),
            'index' => 'cnt',
            'filter' => false
                )
        );

        $this->addColumn(
                'created_at', array(
            'header' => Mage::helper('monogo_applicationmultifb')->__('Created at'),
            'index' => 'created_at',
            'width' => '120px',
            'type' => 'datetime',
            'filter_condition_callback' => array($this, '_customFilter'),
                )
        );
        $this->addColumn(
                'updated_at', array(
            'header' => Mage::helper('monogo_applicationmultifb')->__('Updated at'),
            'index' => 'updated_at',
            'width' => '120px',
            'type' => 'datetime',
            'filter_condition_callback' => array($this, '_customFilter'),
                )
        );

        $this->addColumn(
                'status_name', array(
            'header' => Mage::helper('monogo_application')->__('Status'),
            'index' => 'status_name',
            'type' => 'options',
            'width' => '120px',
            'options' => Mage::getModel('monogo_application/application_status')->getOptionsFilter('multi_application', 'status_name'),
            'renderer' => new Monogo_Application_Block_Adminhtml_Application_Renderer_ColorHex(),
                )
        );

        $this->addExportType('*/*/exportCsv', $this->__('CSV'));

        $this->addExportType('*/*/exportExcel', $this->__('Excel XML'));

        return parent::_prepareColumns();
    }

    public function _customFilter($collection, $column) {
        if (!($value = $column->getFilter()->getValue())) {
            return $this;
        }

        $columnIndex = $column->getFilterIndex() ?
                $column->getFilterIndex() : $column->getIndex();

        if ($columnIndex === 'entity_id') {
            $collection->addFieldToFilter('main_table.entity_id', $value);
        }

        if ($columnIndex === 'customerName') {
            //$value = mysql_real_escape_string($value);
            $collection->getSelect()->where($this->customerSubquery . " like '%$value%'");
        }

        if ($columnIndex === 'created_at') {
            $collection->addFieldToFilter('main_table.created_at', $value);
        }

        if ($columnIndex === 'updated_at') {
            $collection->addFieldToFilter('main_table.updated_at', $value);
        }
    }

    protected function _setCollectionOrder($column) {
        /* @var $collection Monogo_Application_Model_Resource_Application_Collection */
        $collection = $this->getCollection();

        if ($collection) {
            $columnIndex = $column->getFilterIndex() ?
                    $column->getFilterIndex() : $column->getIndex();
            $collection->setOrder($columnIndex, strtoupper($column->getDir()));
        }
        return $this;
    }

    public function getRowUrl($row) {
        return $this->getUrl('*/multiapplication/editMulti', array('id' => $row->getId()));
    }

    /**
     * get the grid url
     *
     * @access public
     * @return string
     * @author
     */
    public function getGridUrl() {
        return $this->getUrl('*/*/grid', array('_current' => true));
    }

    /**
     * after collection load
     *
     * @access protected
     * @return Monogo_Agreement_Block_Adminhtml_Agreement_Grid
     * @author
     */
    protected function _afterLoadCollection() {
        $this->getCollection()->walk('afterLoad');
        parent::_afterLoadCollection();
    }

}
