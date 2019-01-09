<?php

use MediaWiki\Tests\Maintenance\DumpTestCase;

/**
 * Unit tests for Abstractfilter
 *
 * Tests for AbstractFilter::writeOpenPage and AbstractFilter::writeClosePage
 * (and in depth tests of AbstractFilter::writeRevision) are missing, as their
 * working relies on volatile database internals. We test them in the
 * integration tests within BackupDumperAbstractsTest.
 *
 * @group Database
 * @group Dump
 * @covers AbstractFilter
 * @covers NoredirectFilter
 */
class AbstractFilterTest extends DumpTestCase {

	function testRegister() {
		$map = [
			[ 'abstract', 'AbstractFilter', null ],
			[ 'noredirect', 'NoredirectFilter', null ]
		];

		$dumperMock = $this->getMock( 'BackupDumper', [], [], '', false );

		$dumperMock->expects( $this->exactly( count( $map ) ) )
			->method( 'registerFilter' )
			->will( $this->droppingReturnValueMap( $map ) );

		AbstractFilter::register( $dumperMock );
	}

	/**
	 * obtains a stub implementation that allows to map parameters to values
	 * and retract the parameters after usage.
	 *
	 * @param $valueMap array Mapping between parameters and return values. The
	 *              documentation of DroppingReturnValueMap provides an example.
	 * @return DroppingReturnValueMap
	 *
	 * @see DroppingReturnValueMap
	 */
	function droppingReturnValueMap( $valueMap ) {
		return new DroppingReturnValueMap( $valueMap );
	}

	function testWriteOpenStreamNull() {
		$sinkMock = $this->getMock( 'DumpOutput' );

		$sinkMock->expects( $this->exactly( 1 ) )
			->method( 'writeOpenStream' )
			->with( $this->matchesRegularExpression( '@^[[:space:]]*<feed>[[:space:]]*$@' ) );

		// Checking against side effects

		$sinkMock->expects( $this->never() )
			->method( 'writeCloseStream' );

		$sinkMock->expects( $this->never() )
			->method( 'writeOpenPage' );

		$sinkMock->expects( $this->never() )
			->method( 'writeClosePage' );

		$sinkMock->expects( $this->never() )
			->method( 'writeRevision' );

		$sinkMock->expects( $this->never() )
			->method( 'writeLogItem' );

		// Performing the actual test

		$af = new AbstractFilter( $sinkMock );
		$af->writeOpenStream( null );
	}

	function testWriteOpenStreamEmptyString() {
		$sinkMock = $this->getMock( 'DumpOutput' );

		$sinkMock->expects( $this->exactly( 1 ) )
			->method( 'writeOpenStream' )
			->with( $this->matchesRegularExpression( '@^[[:space:]]*<feed>[[:space:]]*$@' ) );

		// Checking against side effects

		$sinkMock->expects( $this->never() )
			->method( 'writeCloseStream' );

		$sinkMock->expects( $this->never() )
			->method( 'writeOpenPage' );

		$sinkMock->expects( $this->never() )
			->method( 'writeClosePage' );

		$sinkMock->expects( $this->never() )
			->method( 'writeRevision' );

		$sinkMock->expects( $this->never() )
			->method( 'writeLogItem' );

		// Performing the actual test

		$af = new AbstractFilter( $sinkMock );
		$af->writeOpenStream( "" );
	}

	function testWriteOpenStreamText() {
		$sinkMock = $this->getMock( 'DumpOutput' );

		$sinkMock->expects( $this->exactly( 1 ) )
			->method( 'writeOpenStream' )
			->with( $this->matchesRegularExpression( '@^[[:space:]]*<feed>[[:space:]]*$@' ) );

		// Checking against side effects

		$sinkMock->expects( $this->never() )
			->method( 'writeCloseStream' );

		$sinkMock->expects( $this->never() )
			->method( 'writeOpenPage' );

		$sinkMock->expects( $this->never() )
			->method( 'writeClosePage' );

		$sinkMock->expects( $this->never() )
			->method( 'writeRevision' );

		$sinkMock->expects( $this->never() )
			->method( 'writeLogItem' );

		// Performing the actual test

		$af = new AbstractFilter( $sinkMock );
		$af->writeOpenStream( "foo" );
	}

	function testWriteCloseStreamNull() {
		$sinkMock = $this->getMock( 'DumpOutput' );

		$sinkMock->expects( $this->exactly( 1 ) )
			->method( 'writeCloseStream' )
			->with( $this->matchesRegularExpression( '@^[[:space:]]*</feed>[[:space:]]*$@' ) );

		// Checking against side effects

		$sinkMock->expects( $this->never() )
			->method( 'writeOpenStream' );

		$sinkMock->expects( $this->never() )
			->method( 'writeOpenPage' );

		$sinkMock->expects( $this->never() )
			->method( 'writeClosePage' );

		$sinkMock->expects( $this->never() )
			->method( 'writeRevision' );

		$sinkMock->expects( $this->never() )
			->method( 'writeLogItem' );

		// Performing the actual test

		$af = new AbstractFilter( $sinkMock );
		$af->writeCloseStream( null );
	}

	function testWriteCloseStreamEmptyString() {
		$sinkMock = $this->getMock( 'DumpOutput' );

		$sinkMock->expects( $this->exactly( 1 ) )
			->method( 'writeCloseStream' )
			->with( $this->matchesRegularExpression( '@^[[:space:]]*</feed>[[:space:]]*$@' ) );

		// Checking against side effects

		$sinkMock->expects( $this->never() )
			->method( 'writeOpenStream' );

		$sinkMock->expects( $this->never() )
			->method( 'writeOpenPage' );

		$sinkMock->expects( $this->never() )
			->method( 'writeClosePage' );

		$sinkMock->expects( $this->never() )
			->method( 'writeRevision' );

		$sinkMock->expects( $this->never() )
			->method( 'writeLogItem' );

		// Performing the actual test

		$af = new AbstractFilter( $sinkMock );
		$af->writeCloseStream( "" );
	}

	function testWriteCloseStreamText() {
		$sinkMock = $this->getMock( 'DumpOutput' );

		$sinkMock->expects( $this->exactly( 1 ) )
			->method( 'writeCloseStream' )
			->with( $this->matchesRegularExpression( '@^[[:space:]]*</feed>[[:space:]]*$@' ) );

		// Checking against side effects

		$sinkMock->expects( $this->never() )
			->method( 'writeOpenStream' );

		$sinkMock->expects( $this->never() )
			->method( 'writeOpenPage' );

		$sinkMock->expects( $this->never() )
			->method( 'writeClosePage' );

		$sinkMock->expects( $this->never() )
			->method( 'writeRevision' );

		$sinkMock->expects( $this->never() )
			->method( 'writeLogItem' );

		// Performing the actual test

		$af = new AbstractFilter( $sinkMock );
		$af->writeCloseStream( "foo" );
	}

	function testWriteRevision() {
		$sinkMock = $this->getMock( 'DumpOutput' );

		// No output of any kind is expected, as the filter outputs only the most current
		// revision, and can detect the most recent only only after all revisions have been
		// passed.

		$sinkMock->expects( $this->never() )
			->method( 'writeOpenStream' );

		$sinkMock->expects( $this->never() )
			->method( 'writeCloseStream' );

		$sinkMock->expects( $this->never() )
			->method( 'writeOpenPage' );

		$sinkMock->expects( $this->never() )
			->method( 'writeClosePage' );

		$sinkMock->expects( $this->never() )
			->method( 'writeRevision' );

		$sinkMock->expects( $this->never() )
			->method( 'writeLogItem' );

		// Performing the actual test

		$af = new AbstractFilter( $sinkMock );
		$af->writeRevision( (object)[], 'bar' );
	}
}
