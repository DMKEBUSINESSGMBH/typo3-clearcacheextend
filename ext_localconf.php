<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc'][]='EXT:clearcacheextend/class.tx_clearcacheextend.php:&tx_clearcacheextend->clear_cacheCmdExtend';

?>