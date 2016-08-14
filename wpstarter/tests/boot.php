<?php
/**
 * This file is part of the WPStarter package.
 *
 * © 2016 Franz Josef Kaiser
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$vendor = dirname( dirname( dirname( __FILE__ ) ) ).'/vendor/';

if ( ! realpath( $vendor ) ) {
	die( 'Please execute Composer installation before running tests.' );
}

require_once "{$vendor}autoload.php";
require_once "{$vendor}phpunit/phpunit/src/Framework/Assert/Functions.php";

unset( $vendor );