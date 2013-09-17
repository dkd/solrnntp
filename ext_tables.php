<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY . '_pimessageview'] = 'layout,select_key';
t3lib_extMgm::addPlugin(
	array('NNTP Message View', $_EXTKEY . '_pimessageview'),
	'list_type'
);


?>