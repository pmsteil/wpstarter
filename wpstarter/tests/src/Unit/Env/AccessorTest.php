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
use Gea\Exception\ReadOnlyWriteException;

class AccessorTest extends TestCase
{
    public function testRead()
    {
        $accessor = new Accessor();

        assertInstanceOf(AccessorInterface::class, $accessor);
    }

    public function testWrite()
    {
        $accessor = new Accessor();

        $_ENV['FOO'] = 'bar';
        try {
            $accessor->write('FOO', 'nope');
        } catch (\Exception $e) {
            assertInstanceOf(ReadOnlyWriteException::class, $e);
            return;
        }

        $this->fail(sprintf(
            'Expected Exception %s has not been raised',
            ReadOnlyWriteException::class
        ));
    }

    public function testDiscard()
    {
        $accessor = new Accessor();

        $_ENV['FOO'] = 'bar';
        try {
            $accessor->discard('FOO');
        } catch (\Exception $e) {
            assertInstanceOf(ReadOnlyWriteException::class, $e);
            return;
        }

        $this->fail(sprintf(
            'Expected Exception %s has not been raised',
            ReadOnlyWriteException::class
        ));
    }
}
