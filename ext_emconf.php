<?php

########################################################################
# Extension Manager/Repository config file for ext: "solrnntp"
#
# Auto generated 18-08-2009 17:45
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'An indexer to search newsgroups with Solr',
	'description' => 'Let\'s you configure NNTP servers and groups to be indexed with Solr. Indexing is done through a EXT:scheduler task.',
	'category' => 'misc',
	'author' => 'Ingo Renner',
	'author_email' => 'ingo@typo3.org',
	'shy' => '',
	'dependencies' => 'solr,scheduler',
	'conflicts' => '',
	'priority' => '',
	'module' => '',
	'state' => 'alpha',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => '',
	'version' => '0.0.0',
	'constraints' => array(
		'depends' => array(
			'solr' => '',
			'scheduler' => '',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:4:{s:9:"ChangeLog";s:4:"2f6b";s:12:"ext_icon.gif";s:4:"1bdc";s:19:"doc/wizard_form.dat";s:4:"ce19";s:20:"doc/wizard_form.html";s:4:"a7e8";}',
);

?>