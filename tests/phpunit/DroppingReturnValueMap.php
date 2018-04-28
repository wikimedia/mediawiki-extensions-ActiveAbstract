<?php

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

	/** @inheritDoc */
	public function __construct( array $valueMap ) {
		$this->valueMap = $valueMap;
	}

	/** @inheritDoc */
	public function invoke( PHPUnit_Framework_MockObject_Invocation $invocation ) {
		if ( isset( $invocation->parameters ) ) {
			// $invocation->parameters is only public in PHPUnit < 6
			$parameters = $invocation->parameters;
		} else {
			// $invocation->getParameters() only exists in PHPUnit 6+
			$parameters = $invocation->getParameters();
		}

		$parameterCount = count( $parameters );

		foreach ( $this->valueMap as $key => $map ) {
			if ( !is_array( $map ) || $parameterCount != count( $map ) - 1 ) {
				continue;
			}

			$return = array_pop( $map );
			if ( $parameters === $map ) {
				unset( $this->valueMap[$key] );

				return $return;
			}
		}

		// Could not find the actual parameters in valueMap. We signal failure, after
		// formatting the actual parameters in $actual, to have a nice error message
		$actual = "(";
		$connective = "";
		foreach ( $parameters as $parameter ) {
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

	/** @inheritDoc */
	public function toString() {
		return 'dropping return value from a map';
	}
}
