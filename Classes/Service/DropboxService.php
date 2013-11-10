<?php

class Tx_DropboxSynchronization_Service_DropboxService implements \TYPO3\CMS\Core\SingletonInterface {

    /**
     * Client identifier for \Dropbox\Client calls.
     * @var string
     */
    private $dropboxClientIdentifier = "TYPO3/dropbox_synchronization";

    /**
     * Will contain the TypoScript configuration if injected.
     * @var Array
     */
    private $configuration = null;

    /**
     * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
     */
    public $configurationManager;

    /**
     * Injects the configuration manager
     *
     * @param \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager
     * @return void
     */
    public function injectConfigurationManager(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager) {
        $this->configurationManager = $configurationManager;
    }

    /**
     * Class constructor autoloading the Dropbox library.
     */
    public function __construct() {
        require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('dropbox_synchronization') . 'Library/Dropbox/autoload.php';
    }

    /**
     * Returns a Dropbox client instance.
     * @return \Dropbox\Client
     * @throws Exception
     */
    private function getClient() {
        if (null == $this->configuration) {
            throw new \Exception('EXT:dropbox_synchronization: no TypoScript injected!');
        }
        return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('\Dropbox\Client', $this->configuration['accessToken'], $this->dropboxClientIdentifier);
    }

    /**
     * Loads the TypoScript of the extension.
     */
    private function loadTypoScript() {
        $configuration = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
        $this->configuration = $configuration['plugin.']['tx_dropboxsynchronization.']['settings.'];
    }

    /**
     * Create the local folder if not existent.
     * @param $folder
     */
    private function ensureLocalFolderExistence($folder) {
        if (!is_dir($folder)) {
            \TYPO3\CMS\Core\Utility\GeneralUtility::mkdir_deep($folder);
        }
    }

    /**
     * Will authorize a dropbox app to access a dropbox account.
     * @param $key
     * @param $secret
     * @param null $authorizationCode
     */
    public function authorizeRequest($key, $secret, $authorizationCode=null) {
        require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('dropbox_synchronization') . 'Library/Dropbox/autoload.php';

        // according to https://www.dropbox.com/developers/core/start/php, mapped to TYPO3
        $appInfo = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('\Dropbox\AppInfo', $key, $secret);
        $webAuth = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('\Dropbox\WebAuthNoRedirect', $appInfo, $this->dropboxClientIdentifier);
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

    /**
     * Will synchronize the configured folder with the Dropbox content.
     * @return bool
     */
    public function synchronize() {
        // initialize prerequisites
        $this->loadTypoScript();
        $folderLocal = PATH_site . $this->configuration['syncFolder'];
        $this->ensureLocalFolderExistence($folderLocal);

        // get all local files
        $filesLocal = array();
        foreach (scandir($folderLocal) as $element) {
            if (!is_dir($folderLocal . '/' . $element)) {
                $filesLocal[] = $element;
            }
        }

        // get all remote files
        $filesRemote = array();
        $dropboxContent = $this->getClient()->getMetadataWithChildren("/");
        foreach ($dropboxContent['contents'] as $element) {
            if (!$element['is_dir']) {
                $filesRemote[] = $element['path'];
            }
        }

//        var_dump(array($filesLocal, $filesRemote));

        // check if there are local files that do not exist remote
        // TODO implement

        // check if there are remote files that do not exist locally
        // TODO implement

        return true;
    }
}