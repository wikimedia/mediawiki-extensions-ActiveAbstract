<?php
if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'ActiveAbstract' );
	/* wfWarn(
		'Deprecated PHP entry point used for ActiveAbstract extension. Please use wfLoadExtension instead, ' .
		'see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	); */
	return;
} else {
	die( 'This version of the ActiveAbstract extension requires MediaWiki 1.25+' );
}
