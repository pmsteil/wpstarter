<?php

/**
 * This file is part of the "" package.
 *
 * © 2016 Franz Josef Kaiser
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WCM\WPStarter\Tests\Env;

use WCM\WPStarter\Tests\TestCase;
use WCM\WPStarter\Env\Accessor;
use Gea\Accessor\AccessorInterface;

class AccessorTest extends TestCase
{
    public function testRead()
    {
        $accessor = new Accessor();

        assertInstanceOf(AccessorInterface::class, $accessor);
    }

    /**
     * @expectedException \Gea\Exception\ReadOnlyWriteException
     */
    public function testWrite()
    {
        $accessor = new Accessor();
        $_ENV['FOO'] = 'bar';
        $accessor->write('FOO', 'nope');
    }

    /**
     * @expectedException \Gea\Exception\ReadOnlyWriteException
     */
    public function testDiscard()
    {
        $accessor = new Accessor();
        $accessor->discard('FOO');
    }
}
