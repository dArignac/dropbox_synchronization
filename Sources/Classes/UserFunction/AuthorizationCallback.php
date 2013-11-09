<?php
/**
 * @package dropbox_synchronization
 */
class Tx_DropboxSynchronization_UserFunction_AuthorizationCallback {

    /**
     * @var Tx_DropboxSynchronization_Service_DropboxService
     */
    public $dropboxService;

    /**
     * Will authenticate your dropbox app.
     * @param $content
     * @param $configuration
     */
    public function respond($content, $configuration) {
        if ($this->dropboxService == null) {
            $this->dropboxService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_DropboxSynchronization_Service_DropboxService');
        }

        $this->dropboxService->authorizeRequest(
            $configuration['dropbox_api.']['key'],
            $configuration['dropbox_api.']['secret'],
            $configuration['dropbox_api.']['authorizationCode']
        );
    }
}