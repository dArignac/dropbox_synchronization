<?php

class Tx_DropboxSynchronization_Scheduler_SchedulerTask extends \TYPO3\CMS\Scheduler\Task\AbstractTask {

    /**
     * @var string
     */
    public $folder;

    /**
     * This is the main method that is called when a task is executed
     * It MUST be implemented by all classes inheriting from this one
     * Note that there is no error handling, errors and failures are expected
     * to be handled and logged by the client implementations.
     * Should return TRUE on successful execution, FALSE on error.
     *
     * @return boolean Returns TRUE on successful execution, FALSE on error
     */
    public function execute() {
        $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Extbase_Object_ObjectManager');
        $service = $objectManager->get('Tx_DropboxSynchronization_Service_DropboxService');
        return $service->synchronize();
    }
}