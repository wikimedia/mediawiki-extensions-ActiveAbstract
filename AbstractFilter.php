<?php

/**
 * Generate XML feed for Yahoo's Active Abstracts project
 * Plugin for dumpBackup.php; call as eg:
 *
 * php dumpBackup.php \
 *   --plugin=AbstractFilter:extensions/ActiveAbstract/AbstractFilter.php \
 *   --current \
 *   --output=gzip:/dumps/abstract.xml.gz \
 *     --filter=namespace:NS_MAIN \
 *     --filter=noredirect \
 *     --filter=abstract
 */

require_once 'includes/EditPage.php'; // hack; for section anchor code

/**
 * Tosses away the MediaWiki XML and generates new output
 */
class AbstractFilter {
	/**
	 * Register the filter function with the dump manager
	 * @param BackupDumper $dumper
	 * @static
	 */
	function register( &$dumper ) {
		$dumper->registerFilter( 'abstract', 'AbstractFilter' );
		$dumper->registerFilter( 'noredirect', 'NoredirectFilter' );
	}
	
	function AbstractFilter( &$sink ) {
		$this->sink =& $sink;
	}
	
	function writeOpenStream( $string ) {
		$this->sink->writeOpenStream( "<feed>\n" );
	}
	
	function writeCloseStream( $string ) {
		$this->sink->writeCloseStream( "</feed>\n" );
	}
	
	function writeOpenPage( $page, $string ) {
		global $wgSitename;
		$this->title = Title::makeTitle( $page->page_namespace, $page->page_title );
		
		$xml = "<doc>\n";
		$xml .= wfElement( 'title', null, $wgSitename . ': ' . $this->title->getPrefixedText() ) . "\n";
		$xml .= wfElement( 'url', null, $this->title->getFullUrl() ) . "\n";
		
		// add abstract and links when we have revision data...
		$this->revision = null;
		
		$this->sink->writeOpenPage( $page, $xml );
	}
	
	function writeClosePage( $string ) {
		$xml = '';
		if( $this->revision ) {
			$xml .= wfElement( 'abstract', null, $this->_abstract( $this->revision ) ) . "\n";
			$xml .= "<links>\n";
			
			$links = $this->_sectionLinks( $this->revision );
			if( empty( $links ) ) {
				// If no TOC, they want us to fall back to categories.
				$links = $this->_categoryLinks( $this->revision );
			}
			foreach( $links as $anchor => $url ) {
				$xml .= $this->_formatLink( $url, $anchor, 'nav' );
			}
			
			// @todo: image links
			
			$xml .= "</links>\n";
		}
		$xml .= "</doc>\n";
		$this->sink->writeClosePage( $xml );
		$this->title = null;
		$this->revision = null;
	}
	
	function writeRevision( $rev, $string ) {
		// Only use one revision's worth of data to output
		$this->revision = $rev;
	}
	
	/**
	 * Extract an abstract from the page
	 * @params object $rev Database rows with revision data
	 * @return string
	 * @access private
	 */
	function _abstract( $rev ) {
		$text = Revision::getRevisionText( $rev ); // FIXME cache this
		
		$stripped = $this->_stripMarkup( $text );
		$extract = $this->_extractStart( $stripped );
		
		return UtfNormal::cleanUp( substr( $extract, 0, 1024 ) ); // not too long pls
	}
	
	/**
	 * Strip markup to show plaintext
	 * @param string $text
	 * @return string
	 * @access private
	 */
	function _stripMarkup( $text ) {
		$text = str_replace( "'''", "", $text );
		$text = str_replace( "''", "", $text );
		$text = preg_replace( '#<!--.*?-->#s', '', $text ); // HTML-style comments
		$text = preg_replace( '#</?[a-z0-9]+.*?>#s', '', $text ); // HTML-style tags
		$text = preg_replace( '#\\[[a-z]+:.*? (.*?)\\]#s', '$1', $text ); // URL links
		$text = preg_replace( '#\\{\\{\\{.*?\\}\\}\\}#s', '', $text ); // template parameters
		$text = preg_replace( '#\\{\\{.*?\\}\\}#s', '', $text ); // template calls
		$text = preg_replace( '#\\[\\[([^|\\]]*\\|)?(.*?)\\]\\]#s', '$2', $text ); // links
		$text = Sanitizer::decodeCharReferences( $text );
		return trim( $text );
	}
	
	/**
	 * Extract the first two sentences, if detectable, from the text.
	 * @param string $text
	 * @return string
	 * @access private
	 */
	function _extractStart( $text ) {
		$endchars = array(
			'.', '!', '?', // regular ASCII
			'。', // full-width ideographic full-stop
			'．', '！', '？', // double-width roman forms
			'｡', // half-width ideographic full stop
			);
		
		$endgroup = implode( '', array_map( 'preg_quote', $endchars ) );
		$end = "[$endgroup]";
		$sentence = ".*?$end+";
		$firsttwo = "/^($sentence$sentence)/";
		
		if( preg_match( $firsttwo, $text, $matches ) ) {
			return $matches[1];
		} else {
			return $text;
		}
	}
	
	/**
	 * Extract a list of TOC links
	 * @param object $rev Database rows with revision data
	 * @return array of URL strings, indexed by name/title
	 * @access private
	 *
	 * @fixme extract TOC items properly
	 * @fixme check for explicit __NOTOC__
	 */
	function _sectionLinks( $rev ) {
		$text = Revision::getRevisionText( $rev );
		$secs =
		  preg_split(
		  '/(^=+.+?=+|^<h[1-6].*?' . '>.*?<\/h[1-6].*?' . '>)(?!\S)/mi',
		  $text, -1,
		  PREG_SPLIT_DELIM_CAPTURE );
		
		$headers = array();
		for( $i = 1; $i < count( $secs ); $i += 2 ) {
			$header = UtfNormal::cleanUp( preg_replace( '/^=+\s*(.*?)\s*=+/', '$1', $secs[$i] ) );
			$anchor = EditPage::sectionAnchor( $header );
			$url = $this->title->getFullUrl() . $anchor;
			$headers[$header] = $url;
		}
		return $headers;
	}
	
	/**
	 * Fetch the list of category links for this page
	 * @param object $rev Database rows with revision data
	 * @return array of URL strings, indexed by category name
	 * @access private
	 */
	function _categoryLinks( $rev ) {
		$id = $rev->page_id;
		$dbr =& wfGetDB( DB_SLAVE );
		$result = $dbr->select( 'categorylinks',
			array( 'cl_to' ),
			array( 'cl_from' => $id ),
			'AbstractFilter::_categoryLinks' );
		
		$links = array();
		while( $row = $dbr->fetchObject( $result ) ) {
			$category = Title::makeTitle( NS_CATEGORY, $row->cl_to );
			$links[$category->getText()] = $category->getFullUrl();
		}
		$dbr->freeResult( $result );
		
		return $links;
	}
	
	/**
	 * Format a <sublink> element, like so:
	 * <sublink linktype="nav">
	 *    <anchor>1939 Births</anchor>
	 *    <link>http://en.wikipedia.org/wiki/Category:1939_births</link>
	 * </sublink>
	 *
	 * @param string $url
	 * @param string $anchor Human-readable link text; eg title or fragment
	 * @param string $linktype "nav" or "image"
	 * @return string XML fragment
	 * @access private
	 */
	function _formatLink( $url, $anchor, $type ) {
		return wfOpenElement( 'sublink', array( 'linktype' => $type ) ) .
			wfElement( 'anchor', null, $anchor ) .
			wfElement( 'link', null, $url ) .
			wfCloseElement( 'sublink' ) . "\n";
	}
	
}

class NoredirectFilter extends DumpFilter {
	function pass( $page, $string ) {
		return !$page->page_is_redirect;
	}
}

?>