<?php

use MediaWiki\Tests\Maintenance\DumpTestCase;

/**
 * Tests for BackupDumper producing abstract dumps using Abstractfilter
 *
 * This test case is not strictlly a unit test, but a crucial integration
 * test for our dump infrastructure
 *
 * @group medium
 * @group Database
 * @group Dump
 * @coversNothing
 */
class BackupDumperAbstractsTest extends DumpTestCase {
	// We'll add several pages, revision and texts. The following variables hold the
	// corresponding ids.
	private $pageId1, $pageId2, $pageId3, $pageId4, $pageId5, $pageId6, $pageId7;
	private $revId1_1, $textId1_1;
	private $revId2_1, $textId2_1, $revId2_2, $textId2_2;
	private $revId3_1, $textId3_1, $revId3_2, $textId3_2;
	private $revId4_1, $textId4_1;
	private $revId5_1, $textId5_1;
	private $revId6_1, $textId6_1;
	private $revId7_1, $textId7_1;

	public function addDBData() {
		$this->tablesUsed[] = 'page';
		$this->tablesUsed[] = 'revision';
		$this->tablesUsed[] = 'text';

		try {
			// Simple Page. With a section, to not trigger category checking,
			// that requires a second connection to the same database (compare
			// Page 6 below)
			$title = Title::newFromText( 'BackupDumperAbstractsTestPage1' );
			$page = WikiPage::factory( $title );
			list( $this->revId1_1, $this->textId1_1 ) = $this->addRevision( $page,
				"BackupDumperAbstractsTestPage1Text1

=Subsection 1=",
				"BackupDumperAbstractsTestPage1Summary1" );
			$this->pageId1 = $page->getId();

			// Page with more than one revision and more subsections
			$title = Title::newFromText( 'BackupDumperAbstractsTestPage2' );
			$page = WikiPage::factory( $title );
			list( $this->revId2_1, $this->textId2_1 ) = $this->addRevision( $page,
				"BackupDumperAbstractsTestPage2Text1",
				"BackupDumperAbstractsTestPage2Summary1" );
			list( $this->revId2_2, $this->textId2_2 ) = $this->addRevision( $page,
				"A short first paragraph.

A second paragraph.


=Subsection 1=
Short paragraph in subsection 1.

Second paragraph in subsection 1.

=Subsection 2=
Short paragraph in subsection 2.

Second paragraph in subsection 2.

==Subsection 2.1==
Short paragraph in subsection 2.1.

Second paragraph in subsection 2.1.

===Subsection 2.1.1===
Short paragraph in subsection 2.1.1.

Second paragraph in subsection 2.1.1.

====Subsection 2.1.1.1====
Short paragraph in subsection 2.1.1.1.

Second paragraph in subsection 2.1.1.1.

==Final & Last subsection==
",
				"BackupDumperAbstractsTestPage1Summary1" );
			$this->pageId2 = $page->getId();

			// Deleted Page
			$title = Title::newFromText( 'BackupDumperAbstractsTestPage3' );
			$page = WikiPage::factory( $title );
			list( $this->revId3_1, $this->textId3_1 ) = $this->addRevision( $page,
				"BackupDumperAbstractsTestPage3Text1", "BackupDumperAbstractsTestPage2Summary1" );
			list( $this->revId3_2, $this->textId3_2 ) = $this->addRevision( $page,
				"BackupDumperAbstractsTestPage3Text2", "BackupDumperAbstractsTestPage2Summary2" );
			$this->pageId3 = $page->getId();

			$page->doDeleteArticleReal(
				"Testing ;)",
				$this->getTestSysop()->getUser()
			);

			// Page in different namespace
			$title = Title::newFromText( 'BackupDumperAbstractsTestPage1', NS_TALK );
			$page = WikiPage::factory( $title );
			list( $this->revId4_1, $this->textId4_1 ) = $this->addRevision( $page,
				"Talk about BackupDumperAbstractsTestPage1 Text1

Second paragraph",
				"Talk BackupDumperAbstractsTestPage1 Summary1" );
			$this->pageId4 = $page->getId();

			// Redirecting page
			$title = Title::newFromText( 'BackupDumperAbstractsTestPage5' );
			$page = WikiPage::factory( $title );
			list( $this->revId5_1, $this->textId5_1 ) = $this->addRevision( $page,
				"#REDIRECT [[Page1]]",
				"BackupDumperAbstractsTestPage5Summary1" );
			$this->pageId5 = $page->getId();

			// Page without subsections
			$title = Title::newFromText( 'BackupDumperAbstractsTestPage6' );
			$page = WikiPage::factory( $title );
			list( $this->revId6_1, $this->textId6_1 ) = $this->addRevision( $page,
				"BackupDumperAbstractsTestPage6Text1",
				"BackupDumperAbstractsTestPage6Summary1" );
			$this->pageId6 = $page->getId();

			// Page with category links
			$title = Title::newFromText( 'BackupDumperAbstractsTestPage7' );
			$page = WikiPage::factory( $title );
			list( $this->revId7_1, $this->textId7_1 ) = $this->addRevision( $page,
				"BackupDumperAbstractsTestPage7Text1

Link to Page1 as Category [[Category:BackupDumperAbstractsTestPage1]].
Link to Page2 as Page [[BackupDumperAbstractsTestPage2]].
Link to Page7 as Category [[Category:BackupDumperAbstractsTestPage7]].
",
				"BackupDumperAbstractsTestPage7Summary1" );
			$this->pageId7 = $page->getId();
		} catch ( Exception $e ) {
			// We'd love to pass $e directly. However, ... see
			// documentation of exceptionFromAddDBData in
			// DumpTestCase
			$this->exceptionFromAddDBData = $e;
		}
	}

	protected function setUp() : void {
		parent::setUp();

		// Since we will restrict dumping by page ranges (to allow
		// working tests, even if the db gets prepopulated by a base
		// class), we have to assert, that the page id are consecutively
		// increasing
		$this->assertEquals(
			[ $this->pageId2, $this->pageId3, $this->pageId4,
				$this->pageId5, $this->pageId6, $this->pageId7 ],
			[ $this->pageId1 + 1, $this->pageId2 + 1, $this->pageId3 + 1,
				$this->pageId4 + 1, $this->pageId5 + 1, $this->pageId6 + 1 ],
			"Page ids increasing without holes" );
	}

	public function testPlain() {
		/**
		 * When dumping pages that contain no subsections (this is what we will to with
		 * pages 6, and 7), AbstractFilter tries to check for the pages' categories to
		 * use as links. Therefore, AbstractFilter grabs a new database connection,
		 * hence does not see the temporary tables created by the test suite. We cannot
		 * add and use dependency injection in AbstractFilter to overcome this, as this
		 * test's database connection is right in the middle of yielding the (unbuffered)
		 * result of querying for the pages/revisions.
		 *
		 * We could of course add means to force buffered resultsets for the dump process,
		 * but this would no longer represent xmldumps-backups use case.
		 *
		 * Long story short: When using temporary tables, we have to skip the test :(
		 */
		if ( $this->usesTemporaryTables() ) {
			$this->markTestSkipped( "This test grabs new database connections at "
				. "several times. Run the test suite with --use-normal-tables "
				. "to not skip this test" );
		}

		// Setting up the dump
		$fname = $this->getNewTempFile();
		$dumper = new DumpBackup( [
			"--plugin=AbstractFilter",
			"--output=file:" . $fname, "--filter=abstract" ] );
		$dumper->startId = $this->pageId1;
		$dumper->endId = $this->pageId4 + 1; // Not including the redirect page (db isolation)
		$dumper->reporting = false;
		$dumper->setDb( $this->db );

		// Performing the dump
		$dumper->dump( WikiExporter::FULL, WikiExporter::TEXT );

		// Checking results
		$this->assertFeedStart( $fname );

		$this->assertDocStart( "BackupDumperAbstractsTestPage1",
			"BackupDumperAbstractsTestPage1Text1" );
		$this->assertLink( "Subsection 1" );
		$this->assertDocEnd();

		// --current is not added in $dumper's constructor. Nevertheless,
		// only the current revision is dumped. This happens on purpose,
		// as documented in AbstractFilter
		$this->assertDocStart( "BackupDumperAbstractsTestPage2",
			"A short first paragraph." );
		$this->assertLink( "Subsection 1" );
		$this->assertLink( "Subsection 2" );
		$this->assertLink( "Subsection 2.1" );
		$this->assertLink( "Subsection 2.1.1" );
		$this->assertLink( "Subsection 2.1.1.1" );
		$this->assertLink( "Final & Last subsection" );
		$this->assertDocEnd();

		// Page 3 is deleted, hence not visible

		$this->assertDocStart( "BackupDumperAbstractsTestPage1",
			"Talk about BackupDumperAbstractsTestPage1 Text1", NS_TALK );
		$this->assertDocEnd();

		$this->assertFeedEnd();
	}

	/**
	 * Opens an XML file to analyze as feed.
	 *
	 * A opening feed tag is asserted and skipped over.
	 *
	 * @param $fname string: name of file to analyze
	 */
	private function assertFeedStart( $fname ) {
		$this->assertDumpStart( $fname, false );
		$this->xml->read();
		$this->assertNodeStart( "feed" );
		$this->skipWhitespace();
	}

	/**
	 * Asserts that the xml reader is at the start of a doc element and skips over the
	 * first tags, after checking them.
	 *
	 * Besides the opening doc element, this function also checks for and skips over the
	 * title, url, and abstract tags and also the opening links element. Hence, after
	 * this function, the xml reader is at the first link element of the doc.
	 *
	 * @param $title string: title of the doc
	 * @param $abstract string: abstract of the doc
	 * @param $ns int: (optional) namespace for the title
	 */
	private function assertDocStart( $title, $abstract, $ns = NS_MAIN ) {
		global $wgSitename;
		$this->assertNodeStart( "doc" );
		$this->skipWhitespace();

		$title = Title::makeTitle( $ns, $title );
		$this->assertNotNull( $title, "Title generation for <doc> tag" );
		$this->assertTextNode( "title", $wgSitename . ": " . $title->getPrefixedText() );

		$this->currentDocURL = $title->getCanonicalURL();
		$this->assertTextNode( "url", $this->currentDocURL );

		$this->assertTextNode( "abstract", $abstract );

		$this->assertNodeStart( "links" );
		$this->skipWhitespace();
	}

	/**
	 * Asserts that the xml reader is at a link element and skips over it, while analyzing it.
	 *
	 * @param $name string: name of the link
	 * @param $is_category bool: (optional) Whether or not the link is a link to a category.
	 *             If true, $name is the categories title without namespace. If false, $name
	 *             is interpreted as name of a subsection within the current doc.
	 */
	private function assertLink( $name, $is_category = false ) {
		$this->assertNodeStart( "sublink" );
		$this->skipWhitespace();

		$this->assertTextNode( "anchor", $name );
		if ( $is_category ) {
			$link = Title::makeTitle( NS_CATEGORY, $name );
			$this->assertTextNode( "link", $link->getCanonicalURL() );
		} else {
			$this->assertTextNode( "link", $this->currentDocURL . "#"
				. str_replace( [ ' ', '&' ], [ '_', '.26' ], $name ) );
		}

		$this->assertNodeEnd( "sublink" );
		$this->skipWhitespace();
	}

	/**
	 * Asserts that the xml reader is after the last link element of the doc.
	 *
	 * The function skips past closing links and doc elements.
	 */
	private function assertDocEnd() {
		$this->assertNodeEnd( "links" );
		$this->skipWhitespace();

		$this->assertNodeEnd( "doc" );
		$this->skipWhitespace();
	}

	/**
	 * Asserts that the xml reader is at the final closing tag of the feed file and
	 * closes the reader.
	 */
	private function assertFeedEnd() {
		$this->assertDumpEnd( "feed" );
	}

	public function testXmlDumpsBackupUseCase() {
		/**
		 * When dumping pages that contain no subsections (this is what we will to with
		 * pages 6, and 7), AbstractFilter tries to check for the pages' categories to
		 * use as links. Therefore, AbstractFilter grabs a new database connection,
		 * hence does not see the temporary tables created by the test suite. We cannot
		 * add and use dependency injection in AbstractFilter to overcome this, as this
		 * test's database connection is right in the middle of yielding the (unbuffered)
		 * result of querying for the pages/revisions.
		 *
		 * We could of course add means to force buffered resultsets for the dump process,
		 * but this would no longer represent xmldumps-backups use case.
		 *
		 * Long story short: When using temporary tables, we have to skip the test :(
		 */
		if ( $this->usesTemporaryTables() ) {
			$this->markTestSkipped( "This test grabs new database connections at "
				. "several times. Run the test suite with --use-normal-tables "
				. "to not skip this test" );
		}

		// Setting up the dump
		$fname = $this->getNewTempFile();
		$dumper = new DumpBackup( [
			"--plugin=AbstractFilter",
			"--current", "--output=file:" . $fname, "--filter=namespace:NS_MAIN",
			"--filter=noredirect", "--filter=abstract"
		] );
		$dumper->startId = $this->pageId1;
		$dumper->endId = $this->pageId7 + 1;

		// xmldumps-backup uses reporting. We will not check the exact reported
		// message, as they are dependent on the processing power of the used
		// computer. We only check that reporting does not crash the dumping
		// and that something is reported
		$dumper->stderr = fopen( 'php://output', 'a' );
		if ( $dumper->stderr === false ) {
			$this->fail( "Could not open stream for stderr" );
		}

		// Performing the dump
		$dumper->dump( WikiExporter::FULL, WikiExporter::TEXT );

		$this->assertTrue( fclose( $dumper->stderr ), "Closing stderr handle" );

		// Checking results
		$this->assertFeedStart( $fname );

		$this->assertDocStart( "BackupDumperAbstractsTestPage1",
			"BackupDumperAbstractsTestPage1Text1" );
		$this->assertLink( "Subsection 1" );
		$this->assertDocEnd();

		// Only the current revision of this page is vilible
		$this->assertDocStart( "BackupDumperAbstractsTestPage2",
			"A short first paragraph." );
		$this->assertLink( "Subsection 1" );
		$this->assertLink( "Subsection 2" );
		$this->assertLink( "Subsection 2.1" );
		$this->assertLink( "Subsection 2.1.1" );
		$this->assertLink( "Subsection 2.1.1.1" );
		$this->assertLink( "Final & Last subsection" );
		$this->assertDocEnd();

		// Page 3 is deleted, hence not visible

		// Page 4 is NS_TALK, hence not visible

		// Page 5 is a redirect, hence not visible

		$this->assertDocStart( "BackupDumperAbstractsTestPage6",
			"BackupDumperAbstractsTestPage6Text1" );
		$this->assertDocEnd();

		$this->assertDocStart( "BackupDumperAbstractsTestPage7",
			"BackupDumperAbstractsTestPage7Text1" );
		$this->assertLink( "BackupDumperAbstractsTestPage1", true );
		$this->assertLink( "BackupDumperAbstractsTestPage7", true );
		$this->assertDocEnd();

		$this->assertFeedEnd();

		$this->expectOutputRegex( '/page.*, [0-9]* revs .*, ETA/' );
	}

}
