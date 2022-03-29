<?php

namespace MediaWiki\Extension\ActiveAbstract;

use DumpFilter;
use stdClass;

class NoredirectFilter extends DumpFilter {
	/**
	 * @param stdClass $page
	 * @return bool
	 */
	protected function pass( $page ) {
		return !$page->page_is_redirect;
	}
}

class_alias( NoredirectFilter::class, 'NoredirectFilter' );
