<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Ingo Renner <ingo@typo3.org>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

require_once(PATH_tslib . 'class.tslib_pibase.php');

require_once($GLOBALS['PATH_solr'] . 'classes/class.tx_solr_template.php');
require_once($GLOBALS['PATH_solr'] . 'classes/class.tx_solr_solrservice.php');
require_once($GLOBALS['PATH_solr'] . 'classes/class.tx_solr_search.php');
require_once($GLOBALS['PATH_solr'] . 'classes/class.tx_solr_query.php');
require_once($GLOBALS['PATH_solr'] . 'classes/class.tx_solr_util.php');

/**
 * Plugin 'NNTP Message View' for the 'solrnntp' extension.
 *
 * @author	Ingo Renner <ingo@typo3.org>
 * @package	TYPO3
 * @subpackage	tx_solrnntp
 */
class tx_solrnntp_PiMessageView extends tslib_pibase {

	public $prefixId      = 'tx_solrnntp';
	public $scriptRelPath = 'pimessageview/class.tx_solrnntp_pimessageview.php';	// Path to this script relative to the extension dir.
	public $extKey        = 'solrnntp';	// The extension key.

	/**
	 * an instance of tx_solr_Search
	 *
	 * @var tx_solr_Search
	 */
	protected $search;

	/**
	 * an instance of tx_solr_Template
	 *
	 * @var tx_solr_Template
	 */
	protected $template;

	/**
	 * Determines whether the solr server is available or not.
	 */
	protected $solrAvailable;

	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	public function main($content, $configuration) {
		$this->initialize($configuration);

		$content = '';
		if ($this->solrAvailable) {
			$article = $this->getArticleDocument();
t3lib_div::devLog('Article', 'solrnntp', 0, array($article));
			$this->template->addVariable('article', $article);

			$content = $this->template->render();
		} else {
			$content = 'Solr is currently not available';
		}

		return $this->pi_wrapInBaseClass($content);
	}

	/**
	 * Initializes the plugin - configuration, language, caching, search...
	 *
	 * @param	array	configuration array as provided by the TYPO3 core
	 * @return	void
	 */
	protected function initialize($configuration) {
		$this->conf = $configuration;

		$this->conf = array_merge(
			$this->conf,
			$GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_solr.']
		);

		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();

		$this->initializeSearch();
		$this->initializeTemplateEngine();

	}

	/**
	 * Initializes the template engine and returns the initialized instance.
	 *
	 * @return	tx_solr_Template
	 */
	protected function initializeTemplateEngine() {
		$templateFile = 'EXT:solrnntp/resources/templates/pimessageview/article.htm';

		$template = t3lib_div::makeInstance(
			'tx_solr_Template',
			$this->cObj,
			$templateFile,
			'article'
		);

		$template->addViewHelperIncludePath('solr', 'classes/viewhelper/');
		$template->addViewHelperIncludePath('solrnntp', 'classes/viewhelper/');

		$this->template = $template;
	}

	protected function initializeSearch() {
		$this->search = t3lib_div::makeInstance('tx_solr_ConnectionManager')->getConnectionByPageId($GLOBALS['TSFE']->id);
		$this->solrAvailable = $this->search->ping();
	}

	protected function getArticleDocument() {
		$query = t3lib_div::makeInstance('tx_solr_Query', '');
		$query->addQueryParameter('qt', 'standard');
		$query->useRawQueryString(TRUE);
		$query->setQueryString('*:*');
		$query->addFilter('id:' . $this->piVars['id']);
		$query->setFieldList('*');

#		$query->addFilter('newsgroup:"' . $this->piVars['newsgroup'] . '"');
#		$query->addFilter('uid:' . $this->piVars['messageUid']);

		$response = $this->search->search($query);
t3lib_div::devLog('Response', 'solrnntp', 0, array($query->getQueryString(), $response));

		return $response->response->docs[0];
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/solrnntp/pimessageview/class.tx_solrnntp_pimessageview.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/solrnntp/pimessageview/class.tx_solrnntp_pimessageview.php']);
}

?>
