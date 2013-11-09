<?php

class Tx_DropboxSynchronization_Service_DropboxService implements \TYPO3\CMS\Core\SingletonInterface {

    /**
      * @var Tx_Extbase_Configuration_ConfigurationManagerInterface
      */
     protected $configurationManager;

     /**
      * @param Tx_Extbase_Configuration_ConfigurationManagerInterface $configurationManager
      * @return void
      */
     public function injectConfigurationManager(Tx_Extbase_Configuration_ConfigurationManagerInterface $configurationManager) {
         $this->configurationManager = $configurationManager;
     }

    public function authorizeRequest($key, $secret, $authorizationCode=null) {
        require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('dropbox_synchronization') . 'Library/Dropbox/autoload.php';

        // according to https://www.dropbox.com/developers/core/start/php, mapped to TYPO3
        $appInfo = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('\Dropbox\AppInfo', $key, $secret);
        $webAuth = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('\Dropbox\WebAuthNoRedirect', $appInfo, "TYPO3");
        $authorizeURL = $webAuth->start();

        echo "To authorize Dropbox access for your app, please open this link and authorize.<br />";
        echo "The Link: <a href='" . $authorizeURL . "'>" . $authorizeURL . "</a><br /><br />";
        echo "Copy the authorization code and set it to the TypoScript setup of this page:<br />";
        echo "<pre>";
        echo "page.123456789.dropbox_api.authorizationCode = THE_CODE";
        echo "</pre>";
        echo "<br />Afterwards reload this page!<br /><br />";

        if (null != $authorizationCode) {
            list($accessToken, $dropboxUserId) = $webAuth->finish($authorizationCode);

            echo "<p style='color:green;'>";
            echo "Dropbox synchronizations setup finished!<br />";
            echo "Finally set this accessToken to your general page TypoScript setup for the extension and disable this page:<br />";
            echo "<pre>plugin.tx_dropboxsynchronization.settings.accessToken = " . $accessToken . "</pre>";
            echo "</p>";
        }

    }
}