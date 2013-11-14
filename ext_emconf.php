<?php

/***************************************************************
 * Extension Manager/Repository config file for ext: "dropbox_synchronization"
 *
 * Auto generated by Extension Builder 2013-11-09
 *
 * Manual updates:
 * Only the data in the array - anything else is removed by next write.
 * "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array(
    'title' => 'Dropbox Synchronization',
    'description' => 'Synchronizes a Dropbox folder with a local TYPO3 folder.',
    'category' => 'misc',
    'author' => 'Alexander Herrmann',
    'author_email' => 'typo3@amnell.de',
    'author_company' => '',
    'shy' => '',
    'priority' => '',
    'module' => '',
    'state' => 'beta',
    'internal' => '',
    'uploadfolder' => '0',
    'createDirs' => '',
    'modify_tables' => '',
    'clearCacheOnLoad' => 0,
    'lockType' => '',
    'version' => '0.3.0',
    'constraints' => array(
        'depends' => array(
            'extbase' => '6.0',
            'fluid' => '6.0',
            'typo3' => '6.0',
        ),
        'conflicts' => array(
        ),
        'suggests' => array(
        ),
    ),
);