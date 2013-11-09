<?php
/**
 * @package dropbox_synchronization
 */
class Tx_DropboxSynchronization_UserFunction_AuthorizationCallback {

    /**
     * @var Tx_DropboxSynchronization_Service_DropboxService
     */
    public $dropboxService;

    public function respond($content, $configuration) {
        if ($this->dropboxService == null) {
            $this->dropboxService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_DropboxSynchronization_Service_DropboxService');
        }
        echo "Tx_DropboxSynchronization_UserFunction_AuthorizationCallback";
    }
}