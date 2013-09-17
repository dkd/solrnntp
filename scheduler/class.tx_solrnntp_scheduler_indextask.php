<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009-2011 Ingo Renner <ingo@typo3.org>
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

	// including PEAR classes
require_once('Log.php');
require_once('Log/null.php');
require_once('Net/NNTP/Client.php');


/**
 * A task to index NNTP groups and their messages
 *
 * @author	Ingo Renner <ingo@typo3.org>
 * @package	TYPO3
 * @subpackage	solrnntp
 */
class tx_solrnntp_scheduler_IndexTask extends tx_scheduler_Task {

		// hard coded for now, as long as we don't need it to be configurable
	protected $nntpConfiguration = array(
		'host'       => 'lists.typo3.org',
		'encryption' => null,
		'port'       => null,
		'wildmat'    => 'typo3.*',
		'logLevel'   => 5, // PEAR_LOG_NOTICE = 5 ; PEAR_LOG_INFO = 6 ; PEAR_LOG_DEBUG = 7
		'timeout'    => null,
		'limit'      => 500
	);

	protected $solrServerConfiguration = array(
		'host' => 'localhost',
		'port' => 8080,
		'paths' => array(
			'/solr/production-typo3solr-en_US/',
			'/solr/production-typo3solr-de_DE/'
		)
	);

	/**
	 * A Solr connection
	 *
	 * @var tx_solr_SolrService
	 */
	protected $solr = null;

	/**
	 * A Solr connection manaager
	 *
	 * @var tx_solr_ConnectionManager
	 */
	protected $connectionManager = null;

	protected $groupsToIndex = array(
		'typo3.dev',
		'typo3.english',
		'typo3.projects.solr',
		'typo3.projects.typo3v4mvc',
		'typo3.projects.v4',
		'typo3.teams.core'
	);

	/**
	 * NNTP Client instance
	 *
	 * @var Net_NNTP_Client
	 */
	protected $nntpClient = null;

	/**
	 * An instance of t3lib_cs to do charset conversions
	 *
	 * @var t3lib_cs
	 */
	protected $charsetConverter = null;

	/**
	 * constructor for class tx_solrnntp_scheduler_IndexTask
	 */
	public function __construct() {
		parent::__construct();

		$logger = Log::singleton('null', '', '', array(), $this->nntpConfiguration['logLevel']);

		$this->nntpClient = new Net_NNTP_Client();
		$this->nntpClient->setLogger($logger);

		$this->charsetConverter = t3lib_div::makeInstance('t3lib_cs');
	}

	/**
	 * Initializes a Solr connection using the given path
	 *
	 * @return	void
	 */
	protected function initializeSolr($path) {
		if (is_null($this->connectionManager)) {
			$this->connectionManager = t3lib_div::makeInstance('tx_solr_ConnectionManager');
		}

		$this->solr = $this->connectionManager->getConnection(
			$this->solrServerConfiguration['host'],
			$this->solrServerConfiguration['port'],
			$path
		);
	}

	/**
	 * Runs through all configured news groups and indexes all new messages
	 * since the last index run.
	 *
	 * @return	boolean	Retruns true on successfull indexing, false on failure.
	 */
	public function execute() {
		$successfullyIndexed = FALSE;
		$this->connect();

		foreach ($this->solrServerConfiguration['paths'] as $path) {
			$this->initializeSolr($path);

			foreach ($this->groupsToIndex as $group) {
				$this->indexGroup($group);
			}

		}

		$this->disconnect();

			// TODO add "real" implementation
		$successfullyIndexed = TRUE;

		return $successfullyIndexed;
	}

	/**
	 * Establishes the NNTP server connection
	 *
	 * @return	boolean	Returns true on success, false on failure.
	 */
	protected function connect() {
		$connected = $this->nntpClient->connect(
			$this->nntpConfiguration['host'],
			$this->nntpConfiguration['encryption'],
			$this->nntpConfiguration['port']
		);

		if (PEAR::isError($connected)) {
			$connected = FALSE;
		}

		return $connected;
	}

	/**
	 * Closes the connection to the NNTP server
	 *
	 * @return	void
	 */
	protected function disconnect() {
		$this->nntpClient->disconnect();
	}

	/**
	 * Iterates through all messages in a specific group to index them
	 *
	 * @return void
	 */
	protected function indexGroup($groupName) {
		$articles     = array();
		$groupSummary = $this->nntpClient->selectGroup($groupName);

		$solrConnectionId = md5(serialize($this->solr));
		$registryKey      = $solrConnectionId . '.lastIndexedArticle.' . $groupName;

		$registry = t3lib_div::makeInstance('t3lib_Registry');
		/* @var $registry t3lib_Registry */
		$lastIndexedArticle = $articleNumber = $registry->get(
			'tx_solrnntp', $registryKey, 'first'
		);

		if ($articleNumber == 'first') {
				// first time indexing
			$articleNumber = $this->nntpClient->first();
		}
		$dummy = $this->nntpClient->selectArticle($articleNumber);

		if (PEAR::isError($dummy)) {
			return;
		}

		for ($i = 0; $i < $this->nntpConfiguration['limit']; $i++) {
				// Break if no more articles
			if ($articleNumber === FALSE) {
				break;
			}

				// Fetch overview for currently selected article
			$article = $this->nntpClient->getArticle();

			if (PEAR::isError($article)) {
				break;
			}
			$this->addArticleToSolr(
				$groupName,
				$articleNumber,
				$this->nntpClient->getHeader(),
				$this->nntpClient->getBody(NULL, TRUE)
			);

			$articles[$articleNumber] = $article;

			$articleNumber = $this->nntpClient->selectNextArticle();
			switch (TRUE) {
				case is_int($articleNumber):
					$lastIndexedArticle = $articleNumber;
					break;
				case PEAR::isError($articleNumber):
					break 2;
			}
		}

		$registry->set('tx_solrnntp', $registryKey, $lastIndexedArticle);

			// forcing cleanup due to issues with the serialized storage of the task through the scheduler
		unset($dummy, $articleNumber, $lastIndexedArticle, $articles, $article, $registry);
	}

	protected function addArticleToSolr($groupName, $articleNumber, $header, $body) {
		$article = array(
			'number' => $articleNumber,
			'header' => $header,
			'body'   => $body
		);

		$solrArticleDocument = $this->nntpArticleToSolrDocument($groupName, $article);
		$this->solr->addDocument($solrArticleDocument);
	}

	/**
	 * Converts a NNTP article to a Solr Document
	 *
	 * @param	string	The newsgroup this article belongs to
	 * @param	array	An array with keys number, header, and body, representing an article
	 * @return	Apache_Solr_Document	The Solr Document representing the person.
	 */
	protected function nntpArticleToSolrDocument($groupName, array $article) {
		$article = $this->convertArticleCharsetToUtf8($article);

		$type     = 'tx_solrnntp_article';
		$document = t3lib_div::makeInstance('Apache_Solr_Document');

		$document->addField('id', tx_solr_Util::getDocumentId(
			$groupName, 1, $article['number'], $type
		));
		$document->addField('appKey', 'EXT:solrnntp');
		$document->addField('type',   $type);

			// system fields
		$document->addField('uid',      $article['number']);
		$document->addField('pid',      1);
		$created = $this->getArticleHeaderField($article['header'], 'Date');
		$created = strtotime($created);
		$document->addField('created',  tx_solr_Util::timestampToIso($created));
		$document->addField('changed',  tx_solr_Util::timestampToIso($created));
		$document->addField('language', 0); // indexing english groups, only

			// content
		$document->addField('title',               htmlspecialchars($this->getArticleHeaderField($article['header'], 'Subject')));
		$document->addField('content',             htmlspecialchars($article['body']));
		$document->addField('author',              $this->getAuthorName($article));
		$document->addField('authorEmail_stringS', $this->getAuthorEmail($article));


			// nntp fields
		$document->addField('messageId_stringS',  $this->getArticleHeaderField($article['header'], 'Message-ID'));
		$document->addField('references_stringS', $this->getArticleHeaderField($article['header'], 'References'));
		$document->addField('newsgroup_stringS',  $groupName);
		$document->addField('articleNumber_intS', $article['number']);
		$document->addField('source_stringS',     $groupName);

		return $document;
	}

	protected function convertArticleCharsetToUtf8(array $article) {
		$articleCharset = $this->getArticleCharset($article['header']);

		if ($articleCharset != 'utf-8') {
			foreach ($article['header'] as $key => $header) {
				$article['header'][$key] = $this->charsetConverter->utf8_encode($header, $articleCharset);
			}

			$article['body'] = $this->charsetConverter->utf8_encode($article['body'], $articleCharset);
		}

		return $article;
	}

	/**
	 * Tries to determine a NNTP article's charset by checking the headers
	 *
	 * @param	array	An array containing an article's headers, one header per line
	 * @return	string	Lowercased charset name. If no charset is found in the header, it's assumed the article is utf-8.
	 */
	protected function getArticleCharset(array $articleHeader) {
		$articleHeader =implode(' ', $articleHeader);
		$charset = 'utf-8'; // assuming the best case

		$matches = array();
		preg_match('/ charset=(.*?) /i', $articleHeader, $matches);

		if (!isset($matches[1])) {
				// cut off the semicolon in case there's one
			$charset = substr($matches[1], -1) == ';' ? substr($matches[1], 0, -1) : $matches[1];
			$charset = strtolower($charset);
		}

		return $charset;
	}

	/**
	 * Tries to find a header field.
	 *
	 * @param	array	An array of headers, one per entry
	 * @param	string	The header name to find.
	 * @return	mixed	The requested header field on success, an empty string otherwise
	 */
	protected function getArticleHeaderField(array $headers, $field) {
		$headerFieldValue = '';

		foreach ($headers as $header) {
			if (t3lib_div::isFirstPartOfStr($header, $field)) {
				$explodedHeader = explode(':', $header, 2);
				$headerFieldValue = trim($explodedHeader[1]);

				break;
			}
		}

		return $headerFieldValue;
	}

	public function getIndexLimit() {
		return $this->nntpConfiguration['limit'];
	}

	public function setIndexLimit($limit) {
		$this->nntpConfiguration['limit'] = (int) $limit;
	}

	public function getGroupsToIndex() {
		return $this->groupsToIndex;
	}

	public function setGroupsToIndex(array $groups) {
		$this->groupsToIndex = $groups;
	}

	/**
	 * Get's an article's author's email. Tries to normalize the email in case
	 * it's wrapped in characters we do not want.
	 *
	 * @param	array	An NNTP article, represented as an array with two fields, header and body
	 * @return	string	The article's author's email
	 */
	protected function getAuthorEmail(array $article) {
		$fromHeader = $this->getArticleHeaderField($article['header'], 'From');

		$from = explode(' ', strrev($fromHeader), 2);
		$fromEmail = strrev($from[0]);

		$fromEmail = str_replace(array('<', '>', '"'), '', $fromEmail);

		return $fromEmail;
	}

	/**
	 * Get's an article's author's name. Tries to normalize the name in case
	 * it's wrapped in characters we do not want.
	 *
	 * @param	array	An NNTP article, represented as an array with two fields, header and body
	 * @return	string	The article's author's name
	 */
	protected function getAuthorName(array $article) {
		$fromHeader = $this->getArticleHeaderField($article['header'], 'From');

		$from       = explode(' ', strrev($fromHeader), 2);
		$fromName   = trim(strrev($from[1]));

		return str_replace('"', '', $fromName);
	}

	/**
	 * This method is designed to return some additional information about the task,
	 * that may help to set it apart from other tasks from the same class
	 * This additional information is used - for example - in the Scheduler's BE module
	 * This method should be implemented in most task classes
	 *
	 * @return	string	Information to display
	 */
	public function getAdditionalInformation() {
		return $this->nntpConfiguration['limit'] . ' Posts from ' . implode(', ', $this->groupsToIndex) . ' => ' . $this->solrServerConfiguration['host'];
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/solrnntp/scheduler/class.tx_solrnntp_scheduler_indextask.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/solrnntp/scheduler/class.tx_solrnntp_scheduler_indextask.php']);
}

?>