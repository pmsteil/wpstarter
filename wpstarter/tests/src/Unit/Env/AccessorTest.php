<?php

/**
 * This file is part of the "" package.
 *
 * © 2016 Franz Josef Kaiser
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WCM\WPStarter\Tests;

use WCM\WPStarter\Env\Accessor;
use Gea\Accessor\AccessorInterface;
use Gea\Exception\ReadOnlyWriteException;

class AccessorTest
{
	public function readTest()
	{
		$accessor = new Accessor();

		assertInstanceOf( AccessorInterface::class, $accessor );
	}

	public function writeTest()
	{

	}
}