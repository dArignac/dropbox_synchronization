<?php

/**
 * Dropbox Synchronization Service.
 * Class Tx_DropboxSynchronization_Service_DropboxService
 */
class Tx_DropboxSynchronization_Service_DropboxService implements \TYPO3\CMS\Core\SingletonInterface {

    /**
     * If to use the feupload integration.
     * @var bool
     */
    private $useFeupload = false;

    /**
     * The feupload file repository.
     * @var Tx_Feupload_Domain_Repository_FileRepository
     */
    private $feuploadRepoFiles;

    /**
     * The feupload user repository.
     * @var Tx_Feupload_Domain_Repository_FrontendUserRepository
     */
    private $feuploadRepoUsers;

    /**
     * The feupload user group repository.
     * @var Tx_Feupload_Domain_Repository_FrontendUserGroupRepository
     */
    private $feuploadRepoUserGroups;

    /**
     * Array of group ids that will be assigned to feupload files.
     * Will be instantiated in initialize() method.
     * @var array
     */
    private $feuploadGroups = array();

    /**
     * The extension key.
     * @var string
     */
    private $extensionKey = 'dropbox_synchronization';

    /**
     * Will contain the TypoScript configuration if injected.
     * @var Array
     */
    private $configuration = null;

    /**
     * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
     */
    protected $configurationManager;

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
     * Returns the client identifier for \Dropbox\Client calls.
     * @return string
     */
    private function getClientIdentifier() {
        return 'TYPO3/' . $this->extensionKey;
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
        return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            '\Dropbox\Client',
            $this->configuration['accessToken'],
            $this->getClientIdentifier()
        );
    }

    /**
     * Loads the TypoScript of the extension.
     */
    private function loadTypoScript() {
        $configuration = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
        $this->configuration = $configuration['plugin.']['tx_dropboxsynchronization.']['settings.'];

        // if feupload is configured additionally fetch the feupload folder
        if (array_key_exists('feupload.', $this->configuration)) {
            $this->configuration['feupload_path'] = $configuration['plugin.']['tx_feupload.']['file.']['path'];
        }
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
     * Returns all files from Dropbox.
     * @return array
     */
    private function getDropboxFiles() {
        $filesRemote = array();
        $dropboxContent = $this->getClient()->getMetadataWithChildren('/');
        foreach ($dropboxContent['contents'] as $element) {
            if (!$element['is_dir']) {
                $filesRemote[] = $element['path'];
            }
        }
        return $filesRemote;
    }

    /**
     * Uploads the given files to Dropbox.
     * @param $folder
     * @param $files
     * @return array
     */
    private function uploadFilesToDropbox($folder, $files) {
        $results = array();

        // upload each file
        foreach ($files as $file) {
            try {
                // upload the file
                $fileHandle = fopen($folder . '/' . $file, 'rb');
                $this->getClient()->uploadFile('/' . $file, \Dropbox\WriteMode::add(), $fileHandle);
                fclose($fileHandle);

                // create feupload file if not already existent
                if ($this->useFeupload) {
                    $this->feuploadCreateFile($folder, $file);
                }

                // result
                $results[$file] = true;
            } catch (\Dropbox\Exception $e) {
                $results[$file] = false;
            }
        }

        // write success/fail states to syslog
        $this->logTransfers('Upload', $results);

        return $results;
    }

    /**
     * Download the given files to the configured folder.
     * @param $folder
     * @param $files
     * @return array
     */
    private function downloadFilesFromDropbox($folder, $files) {
        $results = array();

        // download each file
        foreach ($files as $file) {
            try {
                // download the file
                $fileHandle = fopen($folder . $file, 'w+b');
                $this->getClient()->getFile($file, $fileHandle);
                fclose($fileHandle);

                // if feupload integration, create file locally if not existent
                if ($this->useFeupload) {
                    $this->feuploadCreateFile($folder, $file);
                }

                // mark success
                $results[$file] = true;
            } catch (\Dropbox\Exception $e) {
                $results[$file] = false;
            }
        }

        // write success/fail states to syslog
        $this->logTransfers('Download', $results);

        return $results;
    }

    /**
     * Delete the given files on Dropbox side.
     * @param array $files
     * @return array
     */
    private function deleteFilesInDropbox($files) {
        $results = array();

        foreach ($files as $file) {
            try {
                // delete in Dropbox
                $this->getClient()->delete($file);

                // delete file from feupload if still existent
                if ($this->useFeupload) {
                    $this->feuploadDeleteFile($folder, $file);
                }

                $results[$file] = true;
            } catch (\Dropbox\Exception $e) {
                $results[$file] = false;
            }
        }

        // write success/fail states to syslog
        $this->logTransfers('Deletion', $results);

        return $results;
    }

    /**
     * Returns all local files from the given folder.
     * @param $folder
     * @return array
     */
    private function getTypo3Files($folder) {
        $filesLocal = array();
        foreach (scandir($folder) as $element) {
            if (!is_dir($folder . '/' . $element)) {
                $filesLocal[] = '/' . $element;
            }
        }
        return $filesLocal;
    }

    /**
     * Deletes the given files in the given folder if they exist.
     * @param string $folder
     * @param array $files
     */
    private function deleteFilesInTypo3($folder, $files) {
        foreach ($files as $file) {
            $pathFile = $folder . '/' . $file;
            if (file_exists($pathFile)) {
                if ($this->useFeupload) {
                    $this->feuploadDeleteFile($folder, $file);
                }
                @unlink($pathFile);
            }
        }
    }

    /**
     * Returns a diff with added and deleted files from the perspective of $fileMaster.
     * @param array $filesMaster
     * @param array $filesSlave
     * @return array
     */
    private function getLocationDiff($filesMaster, $filesSlave) {
        return array(
            'added' => array_diff($filesMaster, $filesSlave),
            'deleted' => array_diff($filesSlave, $filesMaster)
        );
    }

    /**
     * Will synchronize the configured folder with the Dropbox content.
     * @return bool
     */
    public function synchronize() {
        // initialize prerequisites
        $this->initialize();
        $folderTypo3 = PATH_site . $this->configuration['syncFolder'];
        $this->ensureLocalFolderExistence($folderTypo3);
        $masterSide = $this->configuration['master'];

        // get all local files
        $filesTypo3 = $this->getTypo3Files($folderTypo3);
        // get all remote files
        $filesDropbox = $this->getDropboxFiles();

        // fetch files to create and delete
        if ($masterSide == 'dropbox') {
            // get the file differences
            $fileDiff = $this->getLocationDiff($filesDropbox, $filesTypo3);

            // download the added files to TYPO3
            $this->downloadFilesFromDropbox($folderTypo3, $fileDiff['added']);

            // delete the removed files in TYPO3
            $this->deleteFilesInTypo3($folderTypo3, $fileDiff['deleted']);
        } else if ($masterSide == 'typo3') {
            // get the file differences
            $fileDiff = $this->getLocationDiff($filesTypo3, $filesDropbox);

            // upload the added files to Dropbox
            $this->uploadFilesToDropbox($folderTypo3, $fileDiff['added']);

            // delete the removed files in Dropbox
            $this->deleteFilesInDropbox($fileDiff['deleted']);
        } else {
            // get the file diff from the perspective of each side. The returned "deleted" elements are the ones that
            // are missing on this side and thus shall be created.
            $fileDiffDropbox = $this->getLocationDiff($filesDropbox, $filesTypo3);
            $fileDiffTypo3 = $this->getLocationDiff($filesTypo3, $filesDropbox);

            // upload TYPO3 files not in Dropbox
            $this->uploadFilesToDropbox($folderTypo3, $fileDiffDropbox['deleted']);

            // download Dropbox files not in TYPO3
            $this->downloadFilesFromDropbox($folderTypo3, $fileDiffTypo3['deleted']);
        }

        // finally persist all db changes for feupload
        if ($this->useFeupload) {
            $persistenceManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Extbase_Persistence_Manager');
            $persistenceManager->persistAll();
        }

        // we will never fail!
        return true;
    }

    /**
     * Initializes the synchronization variables and stuff.
     */
    private function initialize() {
        $this->loadTypoScript();
        $this->useFeupload = array_key_exists('feupload.', $this->configuration);

        // if feupload, initialize all required repos and stuff
        if ($this->useFeupload) {
            // instantiate the feupload repos
            $this->feuploadRepoFiles = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Feupload_Domain_Repository_FileRepository');
            $this->feuploadRepoUsers = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Feupload_Domain_Repository_FrontendUserRepository');
            $this->feuploadRepoUserGroups = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Feupload_Domain_Repository_FrontendUserGroupRepository');

            // fetch the user groups the files should be assigned to
            foreach (explode(',', $this->configuration['feupload.']['initialGroups']) as $groupId) {
                $group = $this->feuploadRepoUserGroups->findByUid(trim($groupId));
                if (null != $group) {
                    $this->feuploadGroups[] = $group;
                }
            }

            // the feupload file repository is setup kinda weird, let's ensure all queries contain the correct storage pid;
            $querySettings = $this->feuploadRepoFiles->createQuery()->getQuerySettings();
            $querySettings->setStoragePageIds(array($this->configuration['feupload.']['storagePid']));
            $this->feuploadRepoFiles->setDefaultQuerySettings($querySettings);
        }
    }

    /**
     * Returns the relative path of the Dropbox folder within the feupload folder.
     * @param $folder
     * @return mixed
     */
    private function feuploadGetRelativePath($folder) {
        $pathDropboxRelative = str_replace(PATH_site, '', $folder);
        return str_replace($this->configuration['feupload_path'], '', $pathDropboxRelative);
    }

    /**
     * Creates the given file as feupload file instance.
     * @param $folder
     * @param $file
     */
    private function feuploadCreateFile($folder, $file) {
        $filePath = $this->feuploadGetRelativePath($folder) . $file; // $file contains leading slash
        // check if this file already exists in DB
        if ($this->feuploadRepoFiles->countByFile($filePath) == 0) {
            $fileInstance = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Feupload_Domain_Model_File');
            $fileInstance->setFile($filePath);
            $fileInstance->setTitle(substr($file, 1));
            $fileInstance->setVisibility($this->configuration['feupload.']['visibility']);
            $fileInstance->setPid($this->configuration['feupload.']['storagePid']);
            $fileInstance->setOwner($this->feuploadRepoUsers->findByUid($this->configuration['feupload.']['userId']));
            foreach ($this->feuploadGroups as $group) {
                $fileInstance->addFrontendUserGroup($group);
            }
            $this->feuploadRepoFiles->add($fileInstance);
        }
    }

    /**
     * Deletes the given file from db (feupload).
     * @param $folder
     * @param $file
     */
    private function feuploadDeleteFile($folder, $file) {
        $filePath = $this->feuploadGetRelativePath($folder) . $file; // $file contains leading slash
        if ($this->feuploadRepoFiles->countByFile($filePath) != 0) {
            $fileInstance = $this->feuploadRepoFiles->findByFile($filePath)->toArray()[0];
            $this->feuploadRepoFiles->remove($fileInstance);
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
     * Logs transfer actions to syslog.
     * @param $type
     * @param $results
     */
    private function logTransfers($type, $results) {
        foreach ($results as $file => $state) {
            \TYPO3\CMS\Core\Utility\GeneralUtility::sysLog(
                $type . ' of ' . $file . ' ' . ($state ? 'was successfull' : 'failed'),
                $this->extensionKey
            );
        }
    }
}