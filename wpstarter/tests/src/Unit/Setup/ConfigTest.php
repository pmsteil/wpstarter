<?php

/**
 * This file is part of the "" package.
 *
 * © 2016 Franz Josef Kaiser
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace WCM\WPStarter\Tests\Setup;

use WCM\WPStarter\Tests\TestCase;
use WCM\WPStarter\Setup\Config;

class ConfigTest extends TestCase
{
	private static $defaults = [
		'gitignore'             => false,
		'env-example'           => true,
		'env-file'              => '.env',
		'move-content'          => false,
		'content-dev-op'        => 'symlink',
		'content-dev-dir'       => 'content-dev',
		'register-theme-folder' => true,
		'prevent-overwrite'     => [ '.gitignore' ],
		'dropins'               => [ ],
		'unknown-dropins'       => 'ask',
	];

	public function setUp()
	{
	}

	/**
	 * Config values may not get changed
	 */
	public function testConfigIsImmutable()
	{
		$config = new Config( self::$defaults );

		try {
			$config['gitignore'] = false;
		}
		catch ( \Exception $e ) {
			assertInstanceOf( \LogicException::class, $e );
			return;
		}

		$this->fail( sprintf(
			'Expected Exception %s has not been raised',
			\LogicException::class
		) );
	}

	/**
	 * Config values may not get unset
	 */
	public function testConfigIsLocked()
	{
		$config = new Config( self::$defaults );

		try {
			unset( $config[ array_rand( self::$defaults ) ] );
		}
		catch ( \Exception $e ) {
			assertInstanceOf( \LogicException::class, $e );
			return;
		}

		$this->fail( sprintf(
			'Expected Exception %s has not been raised',
			\LogicException::class
		) );
	}

	/**
	 * Already existing config values may not get appended
	 */
	public function testConfigIsFrozen()
	{
		$config = new Config( self::$defaults );

		try {
			$config->appendConfig( array_rand( self::$defaults ), 'foo' );
		}
		catch ( \Exception $e ) {
			assertInstanceOf( \BadMethodCallException::class, $e );
			return;
		}

		$this->fail( sprintf(
			'Expected Exception %s has not been raised',
			\BadMethodCallException::class
		) );
	}

	/**
	 * New config values do *not* get validated
	 */
	public function testAppendedConfigWithoutValidation()
	{
		$config = new Config( self::$defaults );

		try {
			$config->appendConfig( 'foo', ';<?php bar' );
		}
		catch ( \Exception $e ) {
			assertInstanceOf( \BadMethodCallException::class, $e );
			return;
		}

		$this->fail( sprintf(
			'Expected Exception %s has not been raised',
			\BadMethodCallException::class
		) );
	}

	/**
	 * New config values do *not* get validated
	 */
	public function testAppendedConfigWithValidation()
	{
		$config = new Config( self::$defaults );

		// Rejects key if provided validation fails
		$config->appendConfig( 'foo', 1.23, function( $value ) {
			return filter_var( $value, FILTER_VALIDATE_INT ) ?: null;
		} );
		assertArrayNotHasKey( 'foo', $config );

		// Custom config needs validation callback
		try {
			$config->appendConfig( 'bar', 'baz' );
		}
		catch ( \Exception $e ) {
			assertInstanceOf( \BadMethodCallException::class, $e );
			return;
		}

		$this->fail( sprintf(
			'Expected Exception %s has not been raised',
			\BadMethodCallException::class
		) );
	}
}