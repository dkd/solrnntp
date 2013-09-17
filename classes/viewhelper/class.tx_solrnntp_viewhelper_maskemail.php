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
 * A viewhelper to mask emails so they can't be indexed by spam harvesters.
 * Replaces viewhelpers ###MASK_EMAIL:xxx###
 *
 * @author	Ingo Renner <ingo@typo3.org>
 * @package TYPO3
 * @subpackage solrnntp
 */
class tx_solrnntp_viewhelper_MaskEmail implements tx_solr_ViewHelper {

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
		$content = '';
		$email   = $arguments[0];

		$explodedEmail = explode('@', $email);
		$content = $explodedEmail[0] . '@...';

		return $content;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/solrnntp/classes/viewhelper/class.tx_solrnntp_viewhelper_formatarticlebody.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/solrnntp/classes/viewhelper/class.tx_solrnntp_viewhelper_formatarticlebody.php']);
}

?>