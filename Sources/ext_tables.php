<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript', 'Dropbox Synchronization');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_dropboxsynchronization_domain_model_dummy', 'EXT:dropbox_synchronization/Resources/Private/Language/locallang_csh_tx_dropboxsynchronization_domain_model_dummy.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_dropboxsynchronization_domain_model_dummy');
$TCA['tx_dropboxsynchronization_domain_model_dummy'] = array(
	'ctrl' => array(
		'title'	=> 'LLL:EXT:dropbox_synchronization/Resources/Private/Language/locallang_db.xlf:tx_dropboxsynchronization_domain_model_dummy',
		'label' => 'uid',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'dividers2tabs' => TRUE,

		'versioningWS' => 2,
		'versioning_followPages' => TRUE,
		'origUid' => 't3_origuid',
		'languageField' => 'sys_language_uid',
		'transOrigPointerField' => 'l10n_parent',
		'transOrigDiffSourceField' => 'l10n_diffsource',
		'delete' => 'deleted',
		'enablecolumns' => array(
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime',
		),
		'searchFields' => '',
		'dynamicConfigFile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Configuration/TCA/Dummy.php',
		'iconfile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . 'Resources/Public/Icons/tx_dropboxsynchronization_domain_model_dummy.gif'
	),
);

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['Tx_DropboxSynchronization_Scheduler_SchedulerTask'] = array(
    'extension'        => $_EXTKEY,
    'title'            => 'Dropbox Synchronization Task',
    'description'      => 'Synchronizes the configured folder with Dropbox contents.',
);