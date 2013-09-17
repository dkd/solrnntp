<?php
if (!defined ('TYPO3_MODE')) {
 	die ('Access denied.');
}


	// adding scheduler tasks
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['tx_solrnntp_scheduler_IndexTask'] = array(
	'extension'        => $_EXTKEY,
	'title'            => 'NNTP Solr Indexer',
	'description'      => 'Indexes Newsgroups and their messages.',
	'additionalFields' => 'tx_solrnntp_scheduler_IndexTaskAdditionalFields'
);

$TYPO3_CONF_VARS['EXTCONF']['solr']['modifySearchQuery']['tx_solrnntp_removeSiteHashFilter'] = 'EXT:solrnntp/classes/class.tx_solrnntp_querymodifier.php:tx_solrnntp_QueryModifier';

	// registering the message view plugin
t3lib_extMgm::addPItoST43(
	$_EXTKEY,
	'pimessageview/class.tx_solrnntp_pimessageview.php',
	'_pimessageview',
	'list_type',
	true
);

?>