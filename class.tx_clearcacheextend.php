<?php
use DmitryDulepov\Realurl\Cache\CacheFactory;
/***************************************************************
 * Copyright notice
 *
 * (c) 2016 DMK E-BUSINESS GmbH <dev@dmk-ebusiness.de>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   62: class tx_clearcacheextend
 *   77:     function clear_cacheCmdExtend($confArray,$objArray)
 *  219:     function hasChanged($objArray,$string)
 *  327:     function processPages($comVal,&$plussArray,&$minusArray)
 *  342:     function processContains($comVal,&$plussArray,&$minusArray,$recursive=false)
 *  507:     function getRelatedUids(&$plussArray,$uid)
 *  566:     function processAlias($comVal,&$plussArray,&$minusArray,$recursive=true)
 *  597:     function processSub($comVal,&$plussArray,&$minusArray)
 *  650:     function processExclude($comVal,&$plussArray,&$minusArray)
 *  702:     function getSubPages($uid,&$plussArray)
 *  722:     function getCommandArrayIntern($string)
 *  742:     function getCommandArray($string)
 *  879:     protected function findRootPid($pageID)
 *  901:     function makeDebug($string,$patern=array(),$replace=array())
 *  925:     private function canBeInterpretedAsInteger($value)
 *
 *
 * TOTAL FUNCTIONS: 14
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */
tx_rnbase::load('tx_rnbase_util_Logger');
tx_rnbase::load('tx_rnbase_util_TCA');
tx_rnbase::load('Tx_Rnbase_Utility_Strings');
tx_rnbase::load('tx_rnbase_util_Typo3Classes');
tx_rnbase::load('tx_rnbase_util_TYPO3');
tx_rnbase::load('tx_rnbase_util_Math');

/**
 *
 * tx_clearcacheextend
 *
 * @package 		TYPO3
 * @subpackage
 * @author 			Hannes Bochmann <hannes.bochmann@dmk-ebusiness.de>
 * @author 			Juraj Sulek <juraj@sulek.sk>
 * @license 		http://www.gnu.org/licenses/lgpl.html
 * 					GNU Lesser General Public License, version 3 or later
 */
class tx_clearcacheextend {

	protected $error=false;
	protected $debugMode=false;
	protected $plussArray=array();
	protected $minusArray=array();
	protected $altePlugins=array();

	/**
	 * function for hook
	 *
	 * @param	array		array obtained from clearCache function
	 * @param	object		object obtained from clearCache function
	 * @return	void
	 */
	public function clear_cacheCmdExtend($confArray,$objArray){
		/* 	this musst be done while this object is created only once and if you enter more then one command
		 *	(e.g. TCEMAIN.clearCacheCmd=sub(..),sub(..),...) then the latter processed commands would work with
		 *	results from earlier commands
		*/

		$this->objArray = $objArray;
		$this->confArray = $confArray;
		$this->error = false;
		$this->debugMode = false;
		$this->showDebug = false;
		$this->debugChar = '';
		unset($this->plussArray);
		$this->plussArray = array();
		unset($this->minusArray);
		$this->minusArray = array();

		$this->error = false;
		$this->altePlugins = array(
			"tt_address"=>"list_type='0'",
			"tt_board"=>"(list_type='2' OR list_type='4')",
			"tt_guest"=>"list_type='3'",
			"tt_products"=>"'list_type='5'",
			"tt_calender"=>"list_type='7'",
			"tt_rating"=>"list_type='8'",
			"tt_news"=>"list_type='9'",
			"tipafriend"=>"list_type='11'",
			"feuser_admin"=>"list_type='20'",
			"direct_mail_subscription"=>"list_type='21'",
			"list"=>";tt_address;tt_board;tt_guest;tt_products;tt_calender;tt_rating;tt_news;tipafriend;feuser_admin;direct_mail_subscription;"

		);

		$getstring=$confArray['cacheCmd'];
		if(is_array($confArray) && $getstring!="" && !$this->canBeInterpretedAsInteger($getstring)){
			$command=substr($getstring,0,strpos($getstring,"("));
			$this->debugChar=substr($getstring,-1);
			if($this->debugChar=="d" || $this->debugChar=="l"){
				if($this->debugChar=="d"){
					$this->showDebug=true;
				}
				$getstring=substr($getstring,0,-1);
				$this->debugMode=true;

				$GLOBALS['LANG']->includeLLFile("EXT:clearcacheextend/locallang.php");
			};
			if($command=='changed'){
				$getstring=$this->hasChanged($objArray,$getstring);
				$command=substr($getstring,0,strpos($getstring,"("));
			};

			if(($command=="alias") || ($command=="sub") || ($command=="contains") || ($command=="pages")){

				$commandArray=$this->getCommandArray($getstring);
				if(!$this->error){
					foreach($commandArray as $comKey=>$comVal){
						switch($comKey){
							case("alias"):{
								$this->processAlias($comVal,$this->plussArray,$this->minusArray,false);
								break;
							};
							case("sub"):{
								$this->processSub($comVal,$this->plussArray,$this->minusArray);
								break;
							};
							case("contains"):{
								$this->processContains($comVal,$this->plussArray,$this->minusArray);
								break;
							}
							case("pages"):{
								$this->processPages($comVal,$this->plussArray,$this->minusArray);
								break;
							}
						}
					}

					if(!$this->error){
						if(count($this->minusArray)>0){
							foreach($this->minusArray as $minusArr){
								if($this->plussArray[$minusArr]==$minusArr){unset($this->plussArray[$minusArr]);}
							};
						};

						if((count($this->plussArray)>0)&&(!$this->debugMode)){
							$pageIds = $GLOBALS['TYPO3_DB']->cleanIntArray($this->plussArray);

							$this->clearTypo3Caches($pageIds);
 							$this->clearRealurlCaches($pageIds);
						};
					};
				};
				if($this->debugMode){
					if(!$this->error){
						tx_rnbase_util_Logger::devLog(
							$GLOBALS['LANG']->getLL("syntax").':'.$GLOBALS['LANG']->getLL("syntax_ok").' ['.$confArray['cacheCmd'].']',
							'clearcacheextend',
							0,
							$GLOBALS['LANG']->getLL("cache_cleared").trim(implode(",",$this->plussArray),",")
						);
						if($this->showDebug){
							debug(
								"<br />".$GLOBALS['LANG']->getLL("syntax")."<strong style=\"color:green;\">".
								$GLOBALS['LANG']->getLL("syntax_ok")."</strong>
								<br /><br />".$GLOBALS['LANG']->getLL("cache_cleared").
								trim(implode(",",$this->plussArray),",")."<br /><br />"
							);
						}
					}else{
						tx_rnbase_util_Logger::devLog(
							$GLOBALS['LANG']->getLL("syntax").':'.$GLOBALS['LANG']->getLL("syntax_error").' ['.$confArray['cacheCmd'].']',
							'clearcacheextend',2
						);
						if($this->showDebug){
							debug(
								"<br />".$GLOBALS['LANG']->getLL("syntax")."<strong style=\"color:red;\">".
								$GLOBALS['LANG']->getLL("syntax_error")."</strong><br />"
							);
						}
					};
				}
			}
		};
	}

	/**
	 * @param array $pageIds
	 * @return void
	 */
	protected function clearTypo3Caches(array $pageIds) {
		if (TYPO3_UseCachingFramework) {
			if(tx_rnbase_util_TYPO3::isTYPO62OrHigher()) {
				$cacheManager = tx_rnbase::makeInstance('TYPO3\\CMS\\Core\\Cache\\CacheManager');
				foreach ($pageIds as $pageId) {
					$cacheManager->flushCachesInGroupByTag('pages', 'pageId_' . (int) $pageId);
				}
			} else {
				$pageCache = $GLOBALS['typo3CacheManager']->getCache('cache_pages');
				$pageSectionCache = $GLOBALS['typo3CacheManager']->getCache('cache_pagesection');

				foreach ($pageIds as $pageId) {
					$pageCache->flushByTag('pageId_' . (int) $pageId);
					$pageSectionCache->flushByTag('pageId_' . (int) $pageId);
				}
			}
		} else {
			$GLOBALS['TYPO3_DB']->exec_DELETEquery('cache_pages', 'page_id IN (' . implode(',', $pageIds) . ')');
			// Originally, cache_pagesection was not cleared with cache_pages!
			$GLOBALS['TYPO3_DB']->exec_DELETEquery('cache_pagesection', 'page_id IN (' . implode(',', $pageIds) . ')');
		}
	}

	/**
	 * @param array $pageIds
	 * @return void
	 */
	protected function clearRealurlCaches(array $pageIds) {
		if(tx_rnbase_util_Extensions::isLoaded('realurl')){
			$realUrlVersionNumber = tx_rnbase_util_Extensions::getExtensionVersion('realurl');
			if (tx_rnbase_util_TYPO3::convertVersionNumberToInteger($realUrlVersionNumber) >= 2000000) {
				foreach ($pageIds as $pageId) {
					CacheFactory::getCache()->clearUrlCacheForPage($pageId);
				}
			} else {
				$GLOBALS['TYPO3_DB']->exec_DELETEquery(
					'tx_realurl_urlencodecache','page_id IN (' . implode(',',$pageIds) . ')'
				);
				$GLOBALS['TYPO3_DB']->exec_DELETEquery(
					'tx_realurl_urldecodecache','page_id IN (' . implode(',',$pageIds) . ')'
				);
			}
		};
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$objArray: ...
	 * @param	[type]		$string: ...
	 * @return	[type]		...
	 */
	protected function hasChanged($objArray,$string){
		/* parse the string to array
		 *	[0] = command
		 *	[1] = true cacheClearCmd
		 *	[2] = string else or d if the else is not used
		 *	[3] = false cacheClearCmd
		 *	[4] = empty field
		*/
		$stringArray=explode("{",$string);
		$newArray=array();
		foreach($stringArray as $key=>$val){
			if($key==0){
				$newArray[]=$val;
			}else{
				$stringArray2=explode("}",$val);
				$newArray[]=$stringArray2[0];
				$newArray[]=$stringArray2[1];
				/* if there are more then one "}" at line envoke an syntax error */
				if(strlen($stringArray2[0])==0){$newArray[0]='none';};
				if(strlen($stringArray2[1])!=0){
					/* this must be here because if i check strpos in an empty string there's a warning */
					if(strpos('}',$stringArray2[1])!==false){$newArray[0]='none';};
				}
			}
		}
		if($newArray[2]!='else'){
			$newArray[3]='none';
		};

		if((substr($newArray[0],0,8)!="changed(") || (substr($newArray[0],-1)!=")") || (strlen($newArray[0]) < 10) || (count($newArray) <3 ) || count($newArray) > 5){
			if($this->debugMode){
				tx_rnbase_util_Logger::devLog($GLOBALS['LANG']->getLL("syntax").':'.$GLOBALS['LANG']->getLL("syntax_error"),'clearcacheextend',2,$GLOBALS['LANG']->getLL("syntax_error_chaged_empty"));
				if($this->showDebug){
					debug(
						"<br />".$GLOBALS['LANG']->getLL("syntax")."<strong class=\"color:red;\">".
						$GLOBALS['LANG']->getLL("syntax_error")."</strong><br />" .
						"<strong>".$GLOBALS['LANG']->getLL("syntax_error_chaged_empty")."</strong><br />"
					);
				}
			}
			return 'none';
		};

		$commandTemp = substr($newArray[0],8,-1);
		$commandTempArray=Tx_Rnbase_Utility_Strings::trimExplode(".",$commandTemp);
		$this->currentCommand = $command = $commandTempArray[0];
		$this->currentCommandField = $field = $commandTempArray[1];

		tx_rnbase_util_TCA::loadTCA($command);
		if(!is_array($GLOBALS['TCA'][$command])){
			if($this->debugMode){
				tx_rnbase_util_Logger::devLog(
					$GLOBALS['LANG']->getLL("syntax").':'.$GLOBALS['LANG']->getLL("syntax_error"),
					'clearcacheextend',
					2,
					str_replace('###table###',$command,$GLOBALS['LANG']->getLL('syntax_error_wrong_table'))
				);
				if($this->showDebug){
					debug(
						"<strong style=\"color:red;\">".
						str_replace('###table###',$command,$GLOBALS['LANG']->getLL('syntax_error_wrong_table')).
						"</strong><br />"
					);
					return 'none';
				}
			}
		};

		if($this->debugMode){
			tx_rnbase_util_Logger::devLog(
				str_replace('###table###',$command,$GLOBALS['LANG']->getLL('syntax_changed_table')),'clearcacheextend',0
			);
			if($this->showDebug){
				debug(
					"<strong>".str_replace('###table###',$command,$GLOBALS['LANG']->getLL('syntax_changed_table')).
					"</strong><br />"
				);
			}
			if($newArray[1]!='none'){
				tx_rnbase_util_Logger::devLog(
					$GLOBALS['LANG']->getLL("syntax_changed_truecommand").$newArray[1],'clearcacheextend',0
				);
				if($this->showDebug){
					debug("<strong>".$GLOBALS['LANG']->getLL("syntax_changed_truecommand").$newArray[1]."</strong><br />");
				}
				$this->clear_cacheCmdExtend(array('cacheCmd'=>$newArray[1].$this->debugChar),$objArray);
			}else{
				tx_rnbase_util_Logger::devLog(
					$GLOBALS['LANG']->getLL("syntax_changed_truecommand").$GLOBALS['LANG']->getLL("syntax_none_command"),
					'clearcacheextend',0
				);
				if($this->showDebug){
					debug(
						"<strong>".$GLOBALS['LANG']->getLL("syntax_changed_truecommand").
						$GLOBALS['LANG']->getLL("syntax_none_command")."</strong><br />"
					);
				}
			};
			if($newArray[3]!='none'){
				tx_rnbase_util_Logger::devLog(
					$GLOBALS['LANG']->getLL("syntax_changed_falsecommand").$newArray[3],'clearcacheextend',0
				);
				if($this->showDebug){
					debug(
						"<strong>".$GLOBALS['LANG']->getLL("syntax_changed_falsecommand").$newArray[3]."</strong><br />"
					);
				}
				$this->clear_cacheCmdExtend(array('cacheCmd'=>$newArray[3].$this->debugChar),$objArray);
			}else{
				tx_rnbase_util_Logger::devLog(
					$GLOBALS['LANG']->getLL("syntax_changed_falsecommand").
					$GLOBALS['LANG']->getLL("syntax_none_command"),
					'clearcacheextend',0
				);
				if($this->showDebug){
					debug(
						"<strong>".$GLOBALS['LANG']->getLL("syntax_changed_falsecommand").
						$GLOBALS['LANG']->getLL("syntax_none_command")."</strong><br />"
					);
				}
			};
		}else{
			if((!$field && is_array($objArray->datamap[$command])) || $objArray->datamap[$command][$objArray->checkValue_currentRecord['uid']][$field] ||	is_array($objArray->cmdmap[$table])){
				$this->clear_cacheCmdExtend(array('cacheCmd'=>$newArray[1]),$objArray);
			}else{
				$this->clear_cacheCmdExtend(array('cacheCmd'=>$newArray[3]),$objArray);
			};
		}
		return 'none';
	}

	/**
	 * function to process the "pages" command
	 *
	 * @param	string		command
	 * @param	array		array with pages where the cache should be cleared
	 * @param	array		array with pages where the cache should be not cleared
	 * @return	void
	 */
	protected function processPages($comVal,&$plussArray,&$minusArray){
		foreach($comVal as $comValKey=>$comValVal){
			$plussArray[$comValVal]=$comValVal;
		};
	}

	/**
	 * function to process the "contains" command
	 *
	 * @param	string		command
	 * @param	array		array with pages where the cache should be cleared
	 * @param	array		array with pages where the cache should be not cleared
	 * @param	bool		false if only pages whoes contain the value should be added to cache clearing and true if subpages should be added too
	 * @return	void
	 */
	protected function processContains($comVal,&$plussArray,&$minusArray,$recursive=false){
		$newPlusArray=array();
		$newMinusArray=array();
		$this->processSub($comVal['roots'],$newPlusArray,$newMinusArray);
		if(count($newMinusArray)>0){
			foreach($newMinusArray as $minusArr){
				if($newPlusArray[$minusArr]==$minusArr){
					unset($newPlusArray[$minusArr]);
				};
			};
		};
		if(count($newPlusArray)>0){
			switch($comVal['type']){
				case("plugin"):{
					$tempPluginName=
					$pluginName=(strpos($this->altePlugins['list'],";".$comVal['plugin'].";")!==false)?$this->altePlugins[$comVal['plugin']]:"(list_type=".$GLOBALS['TYPO3_DB']->fullQuoteStr($comVal['plugin'],"tt_content")." OR list_type LIKE '".addslashes($comVal['plugin'])."_pi%' OR list_type=".$GLOBALS['TYPO3_DB']->fullQuoteStr("tx_".$comVal['plugin'],"tt_content")." OR list_type LIKE 'tx_".addslashes($comVal['plugin'])."_pi%')";
					$res=$GLOBALS['TYPO3_DB']->exec_SELECTquery(
						"pages.uid",
						"pages,tt_content",
						"pages.deleted=0 AND tt_content.deleted=0
						AND pages.hidden=0 and tt_content.hidden=0
						AND tt_content.pid=pages.uid AND tt_content.CType='list' AND pages.uid IN (".trim(implode($newPlusArray,","),",").") AND ".$pluginName);
					if($GLOBALS['TYPO3_DB']->sql_num_rows($res) > 0){
						while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)){
							if($recursive){
								$this->getSubPages($row['uid'],$plussArray);
							}else{
								$plussArray[$row['uid']]=$row['uid'];
								$this->getRelatedUids($plussArray,$row['uid']);
							};
						};
					};
					break;
				};
				case("dam"):{
					$newPlussArrayTemp=array();
					$sqlNewPlusArray=trim(implode($newPlusArray,","),",");
					$plusSQLCategoryNotMM=array('pages'=>'','tt_content'=>'','pages_language_overlay'=>'');

					tx_rnbase_util_TCA::loadTCA("tt_content");
					foreach($GLOBALS['TCA']['tt_content']['columns'] as $TCAKEY=>$TCAVAL){
						if(($TCAVAL['config']['foreign_table']=="tx_dam_cat")||($TCAVAL['config']['foreign_table']=="tx_dam")||($TCAVAL['config']['MM']=="tx_dam_mm_ref")){
							$plusSQLCategoryNotMM['tt_content'].="OR NOT tt_content.".$TCAKEY."=''";
						};
					};

					tx_rnbase_util_TCA::loadTCA("pages");
					foreach($GLOBALS['TCA']['pages']['columns'] as $TCAKEY=>$TCAVAL){
						if(($TCAVAL['config']['foreign_table']=="tx_dam_cat")||($TCAVAL['config']['foreign_table']=="tx_dam")||($TCAVAL['config']['MM']=="tx_dam_mm_ref")){
							$plusSQLCategoryNotMM['pages'].="OR NOT pages.".$TCAKEY."=''";
						};
					};

					tx_rnbase_util_TCA::loadTCA("pages_language_overlay");
					foreach($GLOBALS['TCA']['pages_language_overlay']['columns'] as $TCAKEY=>$TCAVAL){
						if(($TCAVAL['config']['foreign_table']=="tx_dam_cat")||($TCAVAL['config']['foreign_table']=="tx_dam")||($TCAVAL['config']['MM']=="tx_dam_mm_ref")){
							$plusSQLCategoryNotMM['pages_language_overlay'].="OR NOT pages_language_overlay.".$TCAKEY."=''";
						};
					};

					if($plusSQLCategoryNotMM['pages']!=''){
						$resCatNotMMpages=$GLOBALS['TYPO3_DB']->exec_SELECTquery(
							"pages.uid",
							"pages",
							"pages.deleted=0 AND pages.hidden=0 AND (".trim($plusSQLCategoryNotMM['pages'],"OR ").")
							AND pages.uid IN (".$sqlNewPlusArray.")");
						if($GLOBALS['TYPO3_DB']->sql_num_rows($resCatNotMMpages) > 0){
							while ($rowCatNotMMpages = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resCatNotMMpages)){
								$newPlussArrayTemp[$rowCatNotMMpages['uid']]=$rowCatNotMMpages['uid'];
							};
						};
					};

					if($plusSQLCategoryNotMM['tt_content']!=''){
						$resCatNotMMtt_content=$GLOBALS['TYPO3_DB']->exec_SELECTquery(
							"pages.uid",
							"pages,tt_content",
							"pages.deleted=0 AND tt_content.deleted=0
							AND tt_content.hidden=0 AND pages.hidden=0
							AND tt_content.pid=pages.uid AND (".trim($plusSQLCategoryNotMM['tt_content'],"OR ").")
							AND pages.uid IN (".$sqlNewPlusArray.")");
						if($GLOBALS['TYPO3_DB']->sql_num_rows($resCatNotMMtt_content) > 0){
							while ($rowCatNotMMtt_content = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resCatNotMMtt_content)){
								$newPlussArrayTemp[$rowCatNotMMtt_content['uid']]=$rowCatNotMMtt_content['uid'];
							};
						};
					};
					if($plusSQLCategoryNotMM['pages_language_overlay']!=''){
						$resCatNotMMp_l_o=$GLOBALS['TYPO3_DB']->exec_SELECTquery(
							"pages.uid",
							"pages,pages_language_overlay",
							"pages.deleted=0
							AND pages_language_overlay.hidden=0 AND pages.hidden=0
							AND pages_language_overlay.pid=pages.uid AND (".trim($plusSQLCategoryNotMM['pages_language_overlay'],"OR ").")
							AND pages.uid IN (".$sqlNewPlusArray.")");
						if($GLOBALS['TYPO3_DB']->sql_num_rows($resCatNotMMp_l_o) > 0){
							while ($rowCatNotMMp_l_o = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resCatNotMMp_l_o)){
								$newPlussArrayTemp[$rowCatNotMMp_l_o['uid']]=$rowCatNotMMp_l_o['uid'];
							};
						};
					}

					/* process the array from all DAM querys begin */
					foreach($newPlussArrayTemp as $newPluss){
						if($recursive){
							$this->getSubPages($newPluss,$plussArray);
						}else{
							$plussArray[$newPluss]=$newPluss;
							$this->getRelatedUids($plussArray,$newPluss);
						};
					}
					/* process the array from all DAM querys end */
					break;
				};
				case("value"):{
					switch($comVal['fieldtype']){
						case('text'):{
							$fieldVal=$GLOBALS['TYPO3_DB']->fullQuoteStr($comVal['fieldvalue'],$comVal['table']);
							$fieldVal=str_replace("*","%",$fieldVal);
							break;
						};
						case('int'):{
							$fieldVal=intval($comVal['fieldvalue']);
							break;
						};
					};
					$operator=strpos($fieldVal,"%")===false ? "=":" LIKE ";

					if($comVal['table']=="pages"){
						$res=$GLOBALS['TYPO3_DB']->exec_SELECTquery(
							"pages.uid",
							"pages",
							"pages.deleted=0 AND pages.hidden=0
							AND pages.".$comVal['field'].$operator.$fieldVal."
							AND pages.uid IN (".trim(implode($newPlusArray,","),",").")");
					}else{
						$res=$GLOBALS['TYPO3_DB']->exec_SELECTquery(
							"pages.uid",
							"pages,".$comVal['table'],
							"pages.deleted=0 AND pages.hidden=0".($comVal['table']=="tt_content"?" AND tt_content.deleted=0":"")." AND ".$comVal['table'].".hidden=0
							AND pages.uid = ".$comVal['table'].".pid AND ".$comVal['table'].".".$comVal['field'].$operator.$fieldVal."
							AND pages.uid IN (".trim(implode($newPlusArray,","),",").")");
					};
					if($GLOBALS['TYPO3_DB']->sql_num_rows($res) > 0){
						while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
							if($recursive){
								$this->getSubPages($row['uid'],$plussArray);
							}else{
								$plussArray[$row['uid']]=$row['uid'];
								$this->getRelatedUids($plussArray,$row['uid']);
							};
						};
					};
				};
			};
		};
	}

	/**
	 * function to fetch uid's from pages which used "Show content from this page instead:" or the extension "sr_include_pages"
	 *
	 * @param	array		array with pages where the cache should be cleared
	 * @param	uid		uid from the page where the value was found, this function will search if there are any pages referenzing on this uid
	 * @return	void
	 */
	protected function getRelatedUids(&$plussArray,$uid){
		/* Show content from this page instead: */
		$res=$GLOBALS['TYPO3_DB']->exec_SELECTquery(
			"pages.uid",
			"pages",
			"pages.deleted=0 AND pages.hidden=0 AND pages.content_from_pid=".$uid);
		if($GLOBALS['TYPO3_DB']->sql_num_rows($res) > 0){
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)){
				$plussArray[$row['uid']]=$row['uid'];
			}
		}
		/* sr_include_pages */
		if(tx_rnbase_util_Extensions::isLoaded('sr_include_pages')){
			/* this is not so easy while sr_include_pages can get the content recursive */
			/* at first we need to get uids from all levels */
			$i_temp=0;
			$recursive_level[$i_temp]=$uid;
			$uid_temp=$uid;
			while($uid_temp!=0){
				$i_temp++;
				$res_temp=$GLOBALS['TYPO3_DB']->exec_SELECTquery(
					"pages.pid",
					"pages",
					"pages.uid=".$uid_temp);
				$row_temp = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res_temp);
				$uid_temp=$row_temp['pid'];
				$recursive_level[$i_temp]=$uid_temp;
			}
			/* now check all levels to prove it there is sr_include_pages and if it include the page which uid we have become */
			foreach($recursive_level as $key=>$val){
				$res_recursive=$GLOBALS['TYPO3_DB']->exec_SELECTquery(
					"tt_content.pid,tt_content.recursive",
					"tt_content",
					"tt_content.deleted=0 AND tt_content.hidden=0 AND tt_content.CType='sr_include_pages_pi1' AND tt_content.recursive >= ".$key." AND (tt_content.tx_srincludepages_pages='".$val."' OR tt_content.tx_srincludepages_pages LIKE '".$val.",%' OR tt_content.tx_srincludepages_pages LIKE'%,".$val."' OR tt_content.tx_srincludepages_pages LIKE'%,".$val.",%')");
				if($GLOBALS['TYPO3_DB']->sql_num_rows($res_recursive) > 0){
					while ($row_recursive = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res_recursive)){
						$plussArray[$row_recursive['pid']]=$row_recursive['pid'];
					}
				}
			}
		}
		/*
			HERE IS THE PLACE FOR MORE CHECKS

			if you known an extension that include pages and you send me a email and i will put the code here
		*/
	}

	/**
	 * function to process the "alias" command
	 *
	 * @param	string		command
	 * @param	array		array with pages where the cache should be cleared
	 * @param	array		array with pages where the cache should be not cleared
	 * @param	bool		false if only the page with alias should be added to cache clearing and true if subpages should be added too
	 * @return	void
	 */
	protected function processAlias($comVal,&$plussArray,&$minusArray,$recursive=true){
		$likeCom="pages.alias=";
		if(strpos($comVal,"*")!==false){
			$likeCom="pages.alias LIKE";
			$comVal=str_replace("*","%",$comVal);
		}
		$res=$GLOBALS['TYPO3_DB']->exec_SELECTquery(
			"pages.uid",
			"pages",
			"pages.deleted=0 AND pages.hidden=0 AND ".$likeCom.$GLOBALS['TYPO3_DB']->fullQuoteStr($comVal,"pages"));
		if($GLOBALS['TYPO3_DB']->sql_num_rows($res) > 0){
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
				if($recursive){
					$this->getSubPages($row['uid'],$plussArray);
				}else{
					$plussArray[$row['uid']]=$row['uid'];
				}
			}

		};
	}

	/**
	 * function to process the "sub" command
	 *
	 * @param	string		command
	 * @param	array		array with pages where the cache should be cleared
	 * @param	array		array with pages where the cache should be not cleared
	 * @return	void
	 */
	protected function processSub($comVal,&$plussArray,&$minusArray){
		foreach($comVal as $comValKey=>$comValVal){
			switch(key($comValVal)){
				case("pages"):{
					return $this->makeDebug("syntax_error_pages_bud_inserting");
					break;
				};
				case("int"):{
					$this->getSubPages($comValVal['int'],$plussArray);
					break;
				};
				case("alias"):{
					$this->processAlias($comValVal['alias'],$plussArray,$minusArray);
					break;
				};
				case("sub"):{
					$this->processSub($comValVal['sub'],$plussArray,$minusArray);
					break;
				};
				case("exclude"):{
					$newPlusArray=array();
					$newMinusArray=array();
					$this->processExclude($comValVal['exclude'],$newPlusArray,$newMinusArray);
					if(count($newPlusArray)>0){
						foreach($newPlusArray as $plusArr){
							if($newMinusArray[$plusArr]==$plusArr){
								unset($newMinusArray[$plusArr]);
							};
						};
					};
					if(count($newMinusArray)>0){
						foreach($newMinusArray as $minusArr){
							$minusArray[$minusArr]=$minusArr;
						};
					};
					break;
				};
				case("contains"):{
					$this->processContains($comValVal['contains'],$plussArray,$minusArray,true);
					break;
				}
			}
		}
	}

	/**
	 * function to process the "exclude" command
	 *
	 * @param	string		command
	 * @param	array		array with pages where the cache should be cleared
	 * @param	array		array with pages where the cache should be not cleared
	 * @return	void
	 */
	protected function processExclude($comVal,&$plussArray,&$minusArray){
		$newPlusArray=array();
		$newMinusArray=array();
		foreach($comVal as $comValKey=>$comValVal){
			switch(key($comValVal)){
				case("pages"):{
					return $this->makeDebug("syntax_error_pages_bud_inserting");
					break;
				};
				case("int"):{
					$newMinusArray[$comValVal['int']]=$comValVal['int'];
					break;
				};
				case("alias"):{
					$this->processAlias($comValVal['alias'],$newMinusArray,$newPlusArray,false);
					break;
				};
				case("sub"):{
					$this->processSub($comValVal['sub'],$newMinusArray,$newPlusArray);
					break;
				};
				case('exclude'):{
					$this->processExclude($comValVal['exclude'],$newMinusArray,$newPlusArray);
					break;
				};
				case("contains"):{
					$this->processContains($comValVal['contains'],$newMinusArray,$newPlusArray);
					break;
				}
			}
		}
		if(count($newPlusArray)>0){
			foreach($newPlusArray as $plusArr){
				if($newMinusArray[$plusArr]==$plusArr){
					unset($newMinusArray[$plusArr]);
				};
			};
		};
		if(count($newMinusArray)>0){
			foreach($newMinusArray as $minusArr){
				$minusArray[$minusArr]=$minusArr;
			};
		};
	}

	/**
	 * Get all subpages from page and store page id and subpages is to $plussArray
	 *
	 * @param	int		page id
	 * @param	array		array in which the id's should be stored
	 * @return	void
	 */
	protected function getSubPages($uid,&$plussArray){
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			"pages.uid",
			"pages",
			"pages.deleted=0 AND pages.hidden=0 AND pid=".$uid
		);
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			$this->getSubPages($row['uid'], $plussArray);
		}
		if($uid!=0){
			$plussArray[$uid]=$uid;
		};
	}

	/**
	 * internal function used by getCommandArray function
	 *
	 * @param	string		command
	 * @return	array
	 */
	protected function getCommandArrayIntern($string){
		if($this->canBeInterpretedAsInteger($string)){
			return array("int"=>$string);
		} elseif($string=='self'|| $string=='root') {
			$id = $this->currentCommand=='pages'?$this->objArray->checkValue_currentRecord['uid']:$this->objArray->checkValue_currentRecord['pid'];
			$id = ($string=='root' ? $this->findRootPid($id) : $id);
			return array("int"=>$id?$id:-1);
 		}else{
			return $this->getCommandArray($string);
		};
	}

	/**
	 * evaluate command string and make commandArray from this string
	 *
	 * @param	string		command
	 * @return	array
	 */
	protected function getCommandArray($string){
		if($this->error){return array(); }
		$commandEnde=strpos($string,"(");
		if($commandEnde===false || $commandEnde===0 || substr($string,-1)!=")"){$this->error=true; return array();}
		$command=substr($string,0,$commandEnde);
		$innerCommand=substr($string,0,-1);
		$innerCommand=substr($innerCommand,$commandEnde+1);
		$commandArrayIntern=array();
		switch($command){
			case("pages"):{
				$innerArray=Tx_Rnbase_Utility_Strings::trimExplode(";",$innerCommand);
				if(!is_array($innerArray)){return $this->makeDebug("syntax_error_pages_command_not_define_correctly");}
				if(count($innerArray)==0){return $this->makeDebug("syntax_error_pages_command_not_define_correctly");}
				foreach($innerArray as $innerAR){
					if($this->canBeInterpretedAsInteger($innerAR)){
						$commandArrayIntern[$command][]=$innerAR;
					}else{
						return $this->makeDebug("syntax_error_pages_command_only_int");
					};
				};
				return $commandArrayIntern;
				break;
			}
			case("exclude"):
			case("sub"):{
				$bracket=0;
				$innerElement="";
				for($i=0;$i<strlen($innerCommand);$i++){
					if($innerCommand[$i]=="("){$bracket++;}
					if($innerCommand[$i]==")"){$bracket--;}
					if($innerCommand[$i]==";" && $bracket==0){
						$commandArrayIntern[$command][]=$this->getCommandArrayIntern($innerElement);
						$innerElement='';
					}else{
						$innerElement.=$innerCommand[$i];
					}
				}
				if($innerElement!=''){
					$commandArrayIntern[$command][]=$this->getCommandArrayIntern($innerElement);
				}
				return $commandArrayIntern;
				break;
			}
			case("contains"):{
				$bracket=0;
				$containType=substr($innerCommand,0,strpos($innerCommand,";"));
				$innerCommandTemp=substr($innerCommand,strpos($innerCommand,";")+1);
				switch($containType){
					case("plugin"):{
						$commandArrayIntern['contains']['type']="plugin";
						$commandArrayIntern['contains']['plugin']=substr($innerCommandTemp,0,strpos($innerCommandTemp,";"));
						if($commandArrayIntern['contains']['plugin']==""){ return $this->makeDebug("syntax_error_contains_second_parameter_missing");};
						$innerCommandTemp=substr($innerCommandTemp,strpos($innerCommandTemp,";")+1);
						if(!tx_rnbase_util_Extensions::isLoaded($commandArrayIntern['contains']['plugin'])){
							return $this->makeDebug("syntax_error_contains_plugin_doesntinstalled",array("###plugin###"),array($commandArrayIntern['contains']['plugin']));
						};
						break;
					};
					case("dam"):{
						if(!tx_rnbase_util_Extensions::isLoaded('dam')){
							return $this->makeDebug("syntax_error_contains_dam_doesntinstalled");
						};
						$commandArrayIntern['contains']['type']="dam";
						break;
					};
					case("value"):{
						$commandArrayIntern['contains']['type']="value";
						$commandTemp=substr($innerCommandTemp,0,strpos($innerCommandTemp,";"));
						$innerCommandTemp=substr($innerCommandTemp,strpos($innerCommandTemp,";")+1);
						$commandTempArray=Tx_Rnbase_Utility_Strings::trimExplode(".",$commandTemp);
						if($commandTempArray[0]!="pages" && $commandTempArray[0]!="tt_content" && $commandTempArray[0]!='pages_language_overlay'){
							return $this->makeDebug("syntax_error_contains_plugin_valuetables");
						};
						$commandArrayIntern['contains']['table']=$commandTempArray[0];
						$commandTempArray2=Tx_Rnbase_Utility_Strings::trimExplode("=",$commandTempArray[1]);
						tx_rnbase_util_TCA::loadTCA($commandTempArray[0]);
						if(!is_array($GLOBALS['TCA'][$commandTempArray[0]]['columns'][$commandTempArray2[0]])){
							return $this->makeDebug("syntax_error_contains_plugin_fieldnotinTCA",array("###table###","###field###"),array($commandTempArray[0],$commandTempArray2[0]));
						};
						$commandArrayIntern['contains']['field']=$commandTempArray2[0];

						$commandFieldType=array('input'=>'text','select'=>'text','text'=>'text','check'=>'check','group'=>'NA','passthrough'=>'NA');
						if($commandFieldType[$GLOBALS['TCA'][$commandTempArray[0]]['columns'][$commandTempArray2[0]]['config']['type']]=='NA'){
							return $this->makeDebug("syntax_error_contains_plugin_wrongfieldtype",array("###table###","###field###"),array($commandTempArray[0],$commandTempArray2[0]));
						};

						$commandArrayIntern['contains']['fieldtype']=$GLOBALS['TCA'][$commandTempArray[0]]['columns'][$commandTempArray2[0]]['config']['eval']=="int"?"int":$commandFieldType[$GLOBALS['TCA'][$commandTempArray[0]]['columns'][$commandTempArray2[0]]['config']['type']];
						if($commandArrayIntern['contains']['fieldtype']=='int' && !$this->canBeInterpretedAsInteger($commandTempArray2[1])){
							return $this->makeDebug("syntax_error_contains_plugin_wrongvalue_number",array("###table###","###field###","###value###"),array($commandTempArray[0],$commandTempArray2[0],$commandTempArray2[1]));
						};
						$commandArrayIntern['contains']['fieldvalue']=$commandTempArray2[1];
						break;
					}
					default:{
						$this->error=true;
						return array();
						break;
					}
				};
				if($innerCommandTemp==""){$this->error=true; return array();}
				$innerElement="";
				for($i=0;$i<strlen($innerCommandTemp);$i++){
					if($innerCommandTemp[$i]=="("){$bracket++;}
					if($innerCommandTemp[$i]==")"){$bracket--;}
					if($innerCommandTemp[$i]==";" && $bracket==0){
						$commandArrayIntern['contains']['roots'][]=$this->getCommandArrayIntern($innerElement);
						$innerElement="";
					}else{
						$innerElement.=$innerCommandTemp[$i];
					}
				}
				if($innerElement!=""){
					$commandArrayIntern['contains']['roots'][]=$this->getCommandArrayIntern($innerElement);
				}
				return $commandArrayIntern;
				break;
			}
			case("alias"):{
				if($innerCommand==""){return $this->makeDebug("syntax_error_empty_alias");};
				return array("alias"=>$innerCommand);
				break;
			}
			default:{
				$this->error=true;
				return array();
				break;
			}
		}
	}

	/**
	 * Starts at the specified page ID and walks up the tree to find the nearest root page id.
	 * This allows other WEC config modules to work relative to the appropriate root page.
	 *
	 * @param	integer		$pageId
	 * @return	integer
	 */
	protected function findRootPid($pageID) {
		$tsTemplate = tx_rnbase::makeInstance(tx_rnbase_util_Typo3Classes::getExtendedTypoScriptTemplateServiceClass());
		$tsTemplate->tt_track = 0;
		$tsTemplate->init();

		// Gets the rootLine
		$sys_page = tx_rnbase_util_TYPO3::getSysPage();
		$rootLine = $sys_page->getRootLine($pageID);
		$tsTemplate->runThroughTemplates($rootLine);
		$rootPid = $tsTemplate->rootId;

		return $rootPid?$rootPid:$pageID;
	}

	/**
	 * function used in getCommandArray for print debug hits
	 *
	 * @param	string		language label
	 * @param	array		paterns for replace
	 * @param	array		replace strings
	 * @return	array
	 */
	protected function makeDebug($string,$patern=array(),$replace=array()){
		if($this->debugMode){
			if(count($replace)==0){
				tx_rnbase_util_Logger::devLog($GLOBALS['LANG']->getLL("syntax").':'.$GLOBALS['LANG']->getLL("syntax_error"),'clearcacheextend',2,$GLOBALS['LANG']->getLL($string));
				if($this->showDebug){
					echo "<strong style=\"color:red;\">".$GLOBALS['LANG']->getLL($string)."</strong>";
				}
			}else{
				tx_rnbase_util_Logger::devLog($GLOBALS['LANG']->getLL("syntax").':'.$GLOBALS['LANG']->getLL("syntax_error"),'clearcacheextend',2,str_replace($patern,$replace,$GLOBALS['LANG']->getLL($string)));
				if($this->showDebug){
					echo "<strong style=\"color:red;\">".str_replace($patern,$replace,$GLOBALS['LANG']->getLL($string))."</strong>";
				}
			};
		};
		$this->error=true;
		return array();
	}

	/**
	 * Tests if the input can be interpreted as integer.
	 *
	 * @param mixed $value
	 * @return boolean
	 */
	protected function canBeInterpretedAsInteger($value) {
		return tx_rnbase_util_Math::isInteger($value);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/clearcacheextend/class.tx_clearcacheextend.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/clearcacheextend/class.tx_clearcacheextend.php']);
}