<?php
require_once __DIR__ . '/../AbstractFilter.php';

/**
 * An Stub implementation taking return values from an array while removing
 * used parameter/return-value entries therefrom.
 *
 * This stub overcomes limitations of using once() multiple times for different
 * parameter values, while not caring about the invocation order.
 *
 * To get the not-working PHPUnit mock code
 *
 *   mock->expects( $this->once() )
 *       ->method( 'methodName' )
 *       ->parameters( $this->equalTo( param1 ), $this->equalTo( param2 ) )
 *       ->will( $this->returnValue( ret12 ) );
 *   mock->expects( $this->once() )
 *       ->method( 'methodName' )
 *       ->parameters( $this->equalTo( param3 ), $this->equalTo( param4 ) )
 *       ->will( $this->returnValue( ret34 ) );
 *
 * (the above code ignore the first of the two statements) to do what it is
 * supposed to do (i.e.: asserting that methodName is called exactly two times.
 * Once with parameters param1, param2 returning ret12, and once with
 * parameters param3, param4 returning ret34 (which of the two happens first
 * does not matter), you can use the following code:
 *
 *   $map = array(
 *     array( param1, param2, ret12),
 *     array( param3, param4, ret34)
 *   );
 *   mock->expects( $this->exactly( count( $map ) ) )
 *       ->method( 'methodName' )
 *       ->will( new DroppingReturnValueMap( $map ) );
 *
 * This code asserts that each parameter given in $map is used exactly once,
 * while each call returns the given return value.
 *
 *
 * The implementation of DroppingReturnValueMap is based off of
 * PHPUnit_Framework_MockObject_Stub_ReturnValueMap from PHPUnit_MockObject by
 * Sebastian Bergmann (under the 3-clause-BSD licence).
 */
class DroppingReturnValueMap implements PHPUnit_Framework_MockObject_Stub {
	protected $valueMap;

	public function __construct( array $valueMap ) {
		$this->valueMap = $valueMap;
	}

	public function invoke( PHPUnit_Framework_MockObject_Invocation $invocation ) {
		$parameterCount = count( $invocation->parameters );

		foreach ( $this->valueMap as $key => $map ) {
			if ( !is_array( $map ) || $parameterCount != count( $map ) - 1 ) {
				continue;
			}

			$return = array_pop( $map );
			if ( $invocation->parameters === $map ) {
				unset( $this->valueMap[$key] );

				return $return;
			}
		}

		// Could not find the actual parameters in valueMap. We signal failure, after
		// formatting the actual parameters in $actual, to have a nice error message
		$actual = "(";
		$connective = "";
		foreach ( $invocation->parameters as $parameter ) {
			$actual .= $connective . " ";
			try {
				$actual .= strval( $parameter );
			} catch ( Exception $e ) {
				$actual .= "*";
			}
			$connective = ",";
		}
		if ( $parameterCount > 0 ) {
			$actual .= " ";
		}
		$actual .= ")";

		throw new PHPUnit_Framework_ExpectationFailedException( "Map for DroppingReturnValueMap "
			. "does not (or no longer) hold an entry for the actual parameters $actual" );
	}

	public function toString() {
		return 'dropping return value from a map';
	}
}

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
		$af->writeRevision( "foo", "bar" );
	}
}
