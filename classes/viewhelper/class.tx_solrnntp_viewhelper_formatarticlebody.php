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


/**
 * A viewhelper to format NNTP articles, escaping HTML special characters,
 * linking URLs, adding blockquotes.
 * Replaces viewhelpers ###FORMAT_ARTICLE_BODY:xxx###
 *
 * @author	Ingo Renner <ingo@typo3.org>
 * @package TYPO3
 * @subpackage solrnntp
 */
class tx_solrnntp_viewhelper_FormatArticleBody implements tx_solr_ViewHelper {

	/**
	 * constructor for class tx_solrnntp_viewhelper_FormatArticleBody
	 */
	public function __construct(array $arguments = array()) {

	}

	/**
	 * Formats an article body: escapes HTML, links URLs, adds blockquotes
	 *
	 * @param array $arguments
	 * @return	string
	 */
	public function execute(array $arguments = array()) {
		$content     = '';
		$articleBody = $arguments[0];

		$explodedArticleBody = explode("\n", $articleBody);
		$previousQuotingLevel = 0;

		foreach ($explodedArticleBody as $lineNumber => $line) {
				// Code from news.php.net
				// this is some amazingly simplistic code to color quotes/signatures
				// differently, and turn links into real links. it actually appears
				// to work fairly well, but could easily be made more sophistimicated.
#			$line = htmlentities($line, ENT_NOQUOTES, 'utf-8');
			$line = preg_replace("/((mailto|http|ftp|nntp|news):.+?)(&gt;|\\s|\\)|\\.\\s|$)/", "<a href=\"\\1\">\\1</a>\\3", $line);

			$quotingLevel = $this->getQuotingLevel($line);

			if ($previousQuotingLevel < $quotingLevel) {
				$addedQuotingLevels = $quotingLevel - $previousQuotingLevel;
				$content .= str_repeat('<blockquote>', $addedQuotingLevels);
			}

			if ($previousQuotingLevel > $quotingLevel) {
				$removedQuotingLevels = $previousQuotingLevel - $quotingLevel;
				$content .= str_repeat('</blockquote>', $removedQuotingLevels);
			}

				// removing quote indicators
			$line = substr($line, $quotingLevel * 4);
			$content .= $line . "\r\n";

			$previousQuotingLevel = $quotingLevel;
		}

		return $content;
	}

	/**
	 * Resolves how deep a line is quoted
	 *
	 * @param	string	A single line with quote indicating angled brackets at the beginning
	 * @return	integer	Number of nesting level
	 */
	protected function getQuotingLevel($line) {
		$nestingLevel = 0;

		while (t3lib_div::isFirstPartOfStr($line, '&gt;')) {
			$nestingLevel++;
			$line = substr($line, 4);
		}

		return $nestingLevel;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/solrnntp/classes/viewhelper/class.tx_solrnntp_viewhelper_formatarticlebody.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/solrnntp/classes/viewhelper/class.tx_solrnntp_viewhelper_formatarticlebody.php']);
}

?>