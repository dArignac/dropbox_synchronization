<?php

class Tx_DropboxSynchronization_Service_DropboxService implements \TYPO3\CMS\Core\SingletonInterface {

    public function authorizeRequest($key, $secret) {
        require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('dropbox_synchronization') . 'Library/Dropbox/autoload.php';

        // according to https://www.dropbox.com/developers/core/start/php, mapped to TYPO3
        $appInfo = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('\Dropbox\AppInfo', $key, $secret);
        $webAuth = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('\Dropbox\WebAuthNoRedirect', $appInfo, "TYPO3");
        $authorizeURL = $webAuth->start();

        echo "To authorize Dropbox access for your app, please open the link at the end of the page and authorize.<br /><br />";
        echo "Copy the authorization code and set it to TypoScript:<br /><br />";
        echo "<pre>";
        echo "plugin.tx_dropboxsynchronization.settings.authorizationCode = THE_CODE";
        echo "</pre><br />";
        echo "Afterwards disable the page you're currently viewing!<br /><br />";
        echo "The Link: <a href='" . $authorizeURL . "'>" . $authorizeURL . "</a><br />";
    }
}