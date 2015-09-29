<?php

/**
 * Class containing test related event-handlers for FlaggedRevs
 */
class ActiveAbstractTestHooks {
	public static function getUnitTests( &$files ) {
		$files[] = __DIR__ . '/AbstractFilterTest.php';
		$files[] = __DIR__ . '/backup_AbstractTest.php';

		return true;
	}
}
