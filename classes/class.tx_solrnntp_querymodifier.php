<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Ingo Renner <ingo.renner@dkd.de>
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


require_once($GLOBALS['PATH_solr'] . 'interfaces/interface.tx_solr_querymodifier.php');

/**
 * Modifier for queries before being sent to Solr. For now only removes the siteHash filter
 *
 * @author	Ingo Renner <ingo.renner@dkd.de>
 * @package TYPO3
 * @subpackage solrnntp
 */
class tx_solrnntp_QueryModifier implements tx_solr_QueryModifier {

	/**
	 * constructor for class tx_solrnntp_QueryModifier
	 */
	public function __construct() {

	}

	/**
	 * Modifies the given query, currently removes the filter on the siteHash field
	 *
	 * @param	tx_solr_Query	The query to modify
	 * @return	tx_solr_Query	The modified query without the siteHash filter
	 */
	public function modifyQuery(tx_solr_Query $query) {
		$query->removeFilter('siteHash');


		return $query;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/solrnntp/classes/class.tx_solrnntp_querymodifier.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/solrnntp/classes/class.tx_solrnntp_querymodifier.php']);
}

?>