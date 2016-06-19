<?php

/**
 * Class Monogo_ApplicationMultifb_Model_Cron_Status
 */
class Monogo_ApplicationMultifb_Model_Cron_Status {

    public function cron() {
        Mage::log('--CRON START--', 'Monogo_ApplicationMultifb_Model_Cron_Status_cronApplications.log');
        Mage::getSingleton('adminhtml/session')->setData('blockMultiFbEvents', 1); //neeed to block change status event for new multifb
        $this->cronApplications();
        $this->cronMultiApplications(7); // after 7 days by default
        $this->cancelApplications(7); //// after 7 days by default 
        Mage::getSingleton('adminhtml/session')->unsetData('blockMultiFbEvents');
        echo "DONE!";
    }

    /**
     * Applciation from multifb - change status after product configuration time or 15 minutes
     */
    public function cronApplications() {
        try {
            Mage::log('--CRON START--', 'Monogo_ApplicationMultifb_Model_Cron_Status_cronApplications.log');
            $maxLimit = 100;
            $counter = 0;
            $fromStatus = Mage::getModel('monogo_application/application_status')->getDefaultStatus();
            $targetStatus = Mage::getModel('monogo_application/application_status')->load(Monogo_Application_Model_Application_Status::APPLICATION_NEGATIVE_DECISION_STATUS_ID);
            if (!$fromStatus->getId() || !$targetStatus->getId()) {
                Mage::log('ERROR: start or target status not found! Cant contuine..', null, 'Monogo_ApplicationMultifb_Model_Cron_Status_cronApplications.log');
                return;
            }
            /* @var $products Mage_Eav_Model_Entity_Attribute_Set[] */
            $products = Mage::getResourceModel('eav/entity_attribute_set_collection')
                    ->setEntityTypeFilter(Mage::getModel('eav/entity')->setType(Monogo_Application_Model_Application::ENTITY)->getTypeId());
            $resource = Mage::getModel('core/resource');
            /* @var $modelStatusHistory Monogo_Application_Model_Application_Status_History */
            $modelStatusHistory = Mage::getModel('monogo_application/application_status_history');
            foreach ($products as $product) {
                // Interval from product configuration
                $minutesInterval = $product->getData('attribute_set_change_status_after') ? $product->getData('attribute_set_change_status_after') : 15;
                /* @var $applications Monogo_Application_Model_Resource_Application_Collection */
                $applications = Mage::getModel('monogo_application/application')->getCollection();
                $applications->addFieldToFilter('application_multi_id', array('notnull' => true));
                $applications->addFieldToFilter('attribute_set_id', array('eq' => $product->getAttributeSetId()));
                $applications
                        ->getSelect()
                        ->join(array('status_table' => $resource->getTableName('monogo_application/application_status_history')), "e.entity_id = status_table.application_id AND status_table.entity_id = (SELECT MAX(statuses3.entity_id) FROM " . $resource->getTableName('monogo_application/application_status_history') . " statuses3 WHERE statuses3.application_id = e.entity_id) AND status_table.created_at = (SELECT MAX(statuses2.created_at) FROM " . $resource->getTableName('monogo_application/application_status_history') . " statuses2 WHERE statuses2.application_id = e.entity_id)", array())
                        ->join(array('status_origin' => $resource->getTableName('monogo_application/application_status')), "status_origin.entity_id = status_table.status_id ", array())
                        ->where('date_add(e.updated_at, INTERVAL ' . $minutesInterval . ' MINUTE) < NOW()')
                        ->where('status_origin.entity_id = ' . $fromStatus->getId())
                        ->group('e.entity_id');
                if ($applications->count() > 0) {
                    foreach ($applications as $application) {
                        $modelStatusHistory
                                ->clearInstance()
                                ->setData(array(
                                    'status_id' => $targetStatus->getEntityId(),
                                    'status_code' => $targetStatus->getStatusCode(),
                                    'application_id' => $application->getId()
                                ))
                                ->save();
                        $application->setUpdatedAt(date('Y-m-d H:i:s'));
                        $application->save();
                        if ($counter > $maxLimit) {
                            break;
                        }
                        $counter++;
                    }
                }
            }
            Mage::log('Changed statuses for: ' . $counter . ' applications from multifb!', null, 'Monogo_ApplicationMultifb_Model_Cron_Status.log');
        } catch (\Exception $e) {
            Mage::log('ERROR critical!: ' . $e->getMessage(), null, 'Monogo_ApplicationMultifb_Model_Cron_Status_cronApplications.log');
        }
        Mage::log('--CRON END--', 'Monogo_ApplicationMultifb_Model_Cron_Status_cronApplications.log');
        return;
    }

    public function cronMultiApplications($days = 7) {
        try {
            Mage::log('--CRON START--', 'Monogo_ApplicationMultifb_Model_Cron_Status_cronMultiApplications.log');
            $maxLimit = 50;
            $counter = 0;
            $resource = Mage::getModel('core/resource');
            /* @var $modelStatusHistory Monogo_Application_Model_Application_Status_History */
            $modelStatusHistory = Mage::getModel('monogo_application/application_status_history');
            $fromStatus = Mage::getModel('monogo_application/application_status')->getDefaultStatus();
            $targetStatus = Mage::getModel('monogo_application/application_status')->load(Monogo_Application_Model_Application_Status::APPLICATION_NEGATIVE_DECISION_STATUS_ID);
            $fromStatusMulti = Mage::getModel('monogo_application/application_status')->getDefaultStatus('multi_application');
            $targetStatusMulti = Mage::getModel('monogo_application/application_status')->load(Monogo_Application_Model_Application_Status::MULTI_APPLICATION_CANCELED);

            if (!$fromStatus->getId() || !$targetStatus->getId() || !$fromStatusMulti->getId() || !$targetStatusMulti->getId()) {
                Mage::log('ERROR: start or target status not found! Cant contuine..', null, 'Monogo_ApplicationMultifb_Model_Cron_Status_cronMultiApplications.log');
                return;
            }
            /* @var $collection Monogo_ApplicationMultiFb_Model_Resource_Applicationmultifb_Collection */
            $collection = Mage::getModel('monogo_applicationmultifb/applicationmultifb')->getCollection();
            $collection
                    ->getSelect()
                    ->join(array('status_table' => $resource->getTableName('monogo_application/application_status_history')), "main_table.entity_id = status_table.application_multi_id AND status_table.entity_id = (SELECT MAX(statuses3.entity_id) FROM " . $resource->getTableName('monogo_application/application_status_history') . " statuses3 WHERE statuses3.application_multi_id = main_table.entity_id) AND status_table.created_at = (SELECT MAX(statuses2.created_at) FROM " . $resource->getTableName('monogo_application/application_status_history') . " statuses2 WHERE statuses2.application_multi_id = main_table.entity_id)", array())
                    ->join(array('status_origin' => $resource->getTableName('monogo_application/application_status')), "status_origin.entity_id = status_table.status_id ", array())
                    ->where('date_add(main_table.updated_at, INTERVAL ' . $days . ' DAY) < NOW()')
                    ->where('status_origin.entity_id = ' . $fromStatusMulti->getId())
                    ->group('main_table.entity_id');

            if ($collection->count() > 0) {
                foreach ($collection as $mutli) {
                    $modelStatusHistory
                            ->clearInstance()
                            ->setData(array(
                                'status_id' => $targetStatusMulti->getEntityId(),
                                'status_code' => $targetStatusMulti->getStatusCode(),
                                'application_multi_id' => $mutli->getId()
                            ))
                            ->save();
                    $mutli->setUpdatedAt(date('Y-m-d H:i:s'));
                    $mutli->save();
                    /* @var $applications Monogo_Application_Model_Resource_Application_Collection */
                    $applications = Mage::getModel('monogo_application/application')->getCollection();
                    $applications->addFieldToFilter('application_multi_id', array('eq' => $mutli->getId()));

                    foreach ($applications as $application) {
                        $modelStatusHistory
                                ->clearInstance()
                                ->setData(array(
                                    'status_id' => $targetStatus->getEntityId(),
                                    'status_code' => $targetStatus->getStatusCode(),
                                    'application_id' => $application->getId()
                                ))
                                ->save();
                        $application->setUpdatedAt(date('Y-m-d H:i:s'));
                        $application->save();
                    }
                    if ($counter > $maxLimit) {
                        break;
                    }
                    $counter++;
                }
            }

            Mage::log('Changed statuses for: ' . $counter . ' applications multifb!', null, 'Monogo_ApplicationMultifb_Model_Cron_Status_cronMultiApplications.log');
        } catch (\Exception $e) {
            Mage::log('ERROR critical!: ' . $e->getMessage(), null, 'Monogo_ApplicationMultifb_Model_Cron_Status_cronMultiApplications.log');
        }
        Mage::log('--CRON END--', 'Monogo_ApplicationMultifb_Model_Cron_Status_cronMultiApplications.log');
        return;
    }

    /**
     * Cron to cancel application after $dayCount with specific statuses
     * @param type $daysCount
     */
    public function cancelApplications($daysCount = 7) {
        /* @var $collection Monogo_Application_Model_Resource_Application_Collection */
        $collection = Mage::getModel('monogo_application/application')->getCollection();

        $collection
                ->getSelect()
                ->join(array('status_table' => $collection->getTable('monogo_application/application_status_history')), "e.entity_id = status_table.application_id AND status_table.entity_id = (SELECT MAX(statuses3.entity_id) FROM " . $collection->getTable('monogo_application/application_status_history') . " statuses3 WHERE statuses3.application_id = e.entity_id) AND status_table.created_at = (SELECT MAX(statuses2.created_at) FROM " . $collection->getTable('monogo_application/application_status_history') . " statuses2 WHERE statuses2.application_id = e.entity_id)", array())
                ->join(array('status_origin' => $collection->getTable('monogo_application/application_status')), "status_origin.entity_id = status_table.status_id ", array('status_origin.color_hex', 'status_origin.status_name'))
                ->where('e.application_multi_id is not null')
                ->where('status_table.entity_id in (?)', array(
                    Monogo_Application_Model_Application_Status::APPLICATION_POSITIVE_DECISION_STATUS_ID,
                    Monogo_Application_Model_Application_Status::APPLICATION_DOCUMENTS_TO_FILL_STATUS_ID,
                    Monogo_Application_Model_Application_Status::APPLICATION_TO_EDIT_ID,
                ))
                ->where('DATEDIFF(NOW(), status_table.created_at) >= ' . $daysCount) //only with status changed $dayCount or more
                ->limit(50)
                ->group('e.entity_id');
            
        $modelStatusHistory = Mage::getModel('monogo_application/application_status_history');
        $statusCancel = Mage::getModel('monogo_application/application_status')->load(Monogo_Application_Model_Application_Status::APPLICATION_CANCEL_STATUS_ID);
        if ($statusCancel->getId()) {
            foreach ($collection as $row) {
                $newNote = null;
                $actualApplicationHistoryStatus = $row->getActualHistoryStatus(); //get actual status              
                if ((int) $actualApplicationHistoryStatus->getId() !== (int) $statusCancel->getId()) {
                    if ($actualApplicationHistoryStatus->getNoteId()) {
                        $note = Mage::getModel('monogo_notes/note')->load($actualApplicationHistoryStatus->getNoteId());
                        if ($note->getId()) {
                            $dataNote = $note->getData();
                            unset($dataNote['entity_id']);
                            unset($dataNote['created_at']);
                            $dataNote['type'] = 'application';
                            $dataNote['entity_type_id'] = $row->getId();
                            $newNote = Mage::getModel('monogo_notes/note')->setData($dataNote)->save();
                        }
                    }
                    $modelStatusHistory
                            ->clearInstance()
                            ->setData(array(
                                'status_id' => $statusCancel->getId(),
                                'status_code' => $statusCancel->getStatusCode(),
                                'application_multi_id' => $row->getId(),
                                'note_id' => $newNote && $newNote->getId() ? $newNote->getId() : null
                            ))
                            ->save();
                }
            }
            Mage::log('--CRON END--', 'Monogo_ApplicationMultifb_Model_Cron_Status_cronCancelApplications.log');
        }
    }

}
