<?php
/**
 * Class containing test related event-handlers for FlaggedRevs
 */
class ActiveAbstractTestHooks {
	public static function getUnitTests( &$files ) {
		$files[] = dirname( __FILE__ ) . '/AbstractFilterTest.php';
		$files[] = dirname( __FILE__ ) . '/backup_AbstractTest.php';
		return true;
	}
}
