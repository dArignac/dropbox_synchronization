<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript', 'Dropbox Synchronization');

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['Tx_DropboxSynchronization_Scheduler_SchedulerTask'] = array(
    'extension'        => $_EXTKEY,
    'title'            => 'Dropbox Synchronization Task',
    'description'      => 'Synchronizes the configured folder with Dropbox contents.',
);