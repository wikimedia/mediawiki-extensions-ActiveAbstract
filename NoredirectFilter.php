<?php

class NoredirectFilter extends DumpFilter {
	/**
	 * @param stdClass $page
	 * @return bool
	 */
	function pass( $page ) {
		return !$page->page_is_redirect;
	}
}
