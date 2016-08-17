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

use Andrew\StaticProxy;
use WCM\WPStarter\Setup\Steps\StepInterface;
use WCM\WPStarter\Tests\TestCase;
use WCM\WPStarter\Setup\Config;

class ConfigTest extends TestCase
{
    private $defaults = [];

    /**
     * Let's setup defaults to SUT defaults
     */
    public function setUp()
    {
        $proxy = new StaticProxy(Config::class);
        /** @noinspection PhpUndefinedFieldInspection */
        $this->defaults = $proxy->defaults;
        parent::setUp();
    }

    /**
     * Config values may not get changed
     * @expectedException \LogicException
     */
    public function testConfigIsImmutable()
    {
        $config = new Config($this->defaults);
        assertTrue($config['gitignore']);
        $config['gitignore'] = false;
    }

    /**
     * Config values may not get unset
     * @expectedException \LogicException
     */
    public function testConfigIsLocked()
    {
        $config = new Config($this->defaults);
        assertArrayHasKey(array_rand($this->defaults), $config);
        unset($config['key']);
    }

    /**
     * Already existing config values may not get appended
     * @expectedException \BadMethodCallException
     */
    public function testConfigIsFrozen()
    {
        $config = new Config($this->defaults);
        $config->appendConfig(array_rand($this->defaults), 'foo');
    }

    /**
     * New config values needs validation
     * @expectedException \BadMethodCallException
     */
    public function testAppendedConfigWithoutValidation()
    {
        $config = new Config($this->defaults);
        $config->appendConfig('foo', ';<?php bar');
    }

    /**
     * New config values do *not* get validated
     */
    public function testAppendedConfigWithValidation()
    {
        $config = new Config($this->defaults);

        // Rejects key if provided validation fails
        $config->appendConfig('foo', 1.23, function ($value) {
            return filter_var($value, FILTER_VALIDATE_INT) ?: null;
        });

        assertArrayNotHasKey('foo', $config);
    }

    /**
     * When `gitignore` is an array, it only accepts boolean for defaults settings
     * and only accepts strings for "custom" entries
     */
    public function testValidateGitIgnoreArray()
    {
        $ignore_array = [
            'custom'     => [
                'foo' => '/foo',
                'bar' => 1,
                'baz' => true
            ],
            'wp'         => false,
            'wp-content' => false,
            'vendor'     => true,
            'common'     => true,
            'meh'        => true
        ];

        $expected = [
            'custom'     => [
                'foo' => '/foo', // only custom strings should be kept
            ],
            'wp'         => false,
            'wp-content' => false,
            'vendor'     => true,
            'common'     => true,
        ];

        $config = new Config(['gitignore' => $ignore_array]);
        assertSame($expected, $config['gitignore']);
    }

    /**
     * when `'prevent-overwrite` is set to string "hard" (case insensitive)
     * it has to be set to "hard"
     */
    public function testValidateOverwriteHard()
    {
        foreach (['hard', 'Hard', 'HARD'] as $hard) {
            $config = new Config(['prevent-overwrite' => $hard]);
            assertSame('hard', $config['prevent-overwrite']);
        }
    }

    /**
     * Verbosity can only be a integer between 0 and 2 (included) or null
     * @dataProvider verbosityTestData
     * @param mixed $argument
     * @param int|null $expected
     */
    public function testValidateVerbosity($argument, $expected)
    {
        $config = new Config(['verbosity' => $argument]);
        if ($expected === null) {
            assertArrayNotHasKey('verbosity', $config);
        }

        if (is_int($expected)) {
            assertSame($expected, $config['verbosity']);
        }
    }

    public function verbosityTestData()
    {
        return [
            [-1, null],
            [0, 0],
            [1, 1],
            [2, 2],
            [3, null],
            [7, null],
            ['foo', null],
            [true, null],
        ];
    }

    /**
     * Paths are sanitized with normalized slashes
     * @dataProvider pathArrayTestData
     */
    public function testValidatePath()
    {
        $config = new Config(['content-dev-dir' => '§foo§/§bar§\§baz§/§meh§/foo§']);
        assertSame('foo/bar/baz/meh/foo', $config['content-dev-dir']);
    }

    /**
     * @dataProvider pathArrayTestData
     * @param mixed $argument
     * @param array $expected
     */
    public function testValidatePathArray($argument, $expected)
    {
        $config = new Config(['dropins' => $argument]);
        // we don't care about keys...
        assertSame(array_values($expected), array_values($config['dropins']));
    }

    public function pathArrayTestData()
    {
        return [
            ['foo', []],
            // not arrays, skipped and valus is empty array
            [1, []],
            // not arrays, skipped and valus is empty array
            [true, []],
            // not arrays, skipped and valus is empty array
            [[], []],
            [['foo\bar\baz\meh\foo', 'foo/bar/baz/meh/foo'], ['foo/bar/baz/meh/foo']],
            // unique...
            [[1, 2, 'foo/bar/baz/meh/foo', '\meh'], ['foo/bar/baz/meh/foo', '/meh']],
            // not string skipped
        ];
    }

    /**
     * @dataProvider boolAskUrlTestData
     * @param $argument
     * @param $expected
     */
    public function testValidateBoolOrAskOrUrl($argument, $expected)
    {
        $config = new Config(['env-example' => $argument]);
        // invalid value, which returns null, are then set to default
        is_null($expected) and $expected = $this->defaults['env-example'];

        assertSame($expected, $config['env-example']);
    }

    public function boolAskUrlTestData()
    {
        return [
            [true, true], // booleans-alike stay booleans
            [false, false], // booleans-alike stay booleans
            [1, true], // booleans-alike stay booleans
            [0, false], // booleans-alike stay booleans
            ["true", true], // booleans-alike stay booleans
            ["false", false], // booleans-alike stay booleans
            ["1", true], // booleans-alike stay booleans
            ["0", false], // booleans-alike stay booleans
            ["yes", true], // booleans-alike stay booleans
            ["no", false], // booleans-alike stay booleans
            ["on", true], // booleans-alike stay booleans
            ["off", false], // booleans-alike stay booleans
            ['ask', 'ask'], // ask-alike, are "ask"
            ['prompt', 'ask'], // ask-alike, are "ask"
            ['query', 'ask'], // ask-alike, are "ask"
            ['interrogate', 'ask'], // ask-alike, are "ask"
            ['demand', 'ask'], // ask-alike, are "ask"
            [12, null], // strange things, are null
            [[], null], // strange things, are null
            [new \stdClass(), null], // strange things, are null
            ['§/foo/§bar', '/foo/bar'], // URLs are sanitized
        ];
    }

    /**
     * @dataProvider boolAskTestData
     * @param mixed $argument
     * @param bool|string $expected
     */
    public function testValidateBoolOrAsk($argument, $expected)
    {
        $config = new Config(['unknown-dropins' => $argument]);
        // invalid value, which returns null, are then set to default
        is_null($expected) and $expected = $this->defaults['unknown-dropins'];

        assertSame($expected, $config['unknown-dropins']);
    }

    public function boolAskTestData()
    {
        return [
            [true, true], // booleans-alike stay booleans
            [false, false], // booleans-alike stay booleans
            [1, true], // booleans-alike stay booleans
            [0, false], // booleans-alike stay booleans
            ["true", true], // booleans-alike stay booleans
            ["false", false], // booleans-alike stay booleans
            ["1", true], // booleans-alike stay booleans
            ["0", false], // booleans-alike stay booleans
            ["yes", true], // booleans-alike stay booleans
            ["no", false], // booleans-alike stay booleans
            ["on", true], // booleans-alike stay booleans
            ["off", false], // booleans-alike stay booleans*/
            ['ask', 'ask'], // ask-alike, are "ask"
            ['prompt', 'ask'], // ask-alike, are "ask"
            ['query', 'ask'], // ask-alike, are "ask"
            ['interrogate', 'ask'], // ask-alike, are "ask"
            ['demand', 'ask'], // ask-alike, are "ask"
            [12, null], // other things, are null
            [[], null], // other things, are null
            [new \stdClass(), null], // other things, are null
            ['/foo/bar', null], // other are sanitized
        ];
    }

    /**
     * @dataProvider stepsTestData
     * @param mixed $argument
     * @param array|null $expected
     */
    public function testValidateSteps($argument, $expected)
    {
        $config = new Config(['custom-steps' => $argument]);

        if (is_null($expected)) {
            assertArrayNotHasKey('custom-steps', $config);
        } else {
            assertSame($expected, $config['custom-steps']);
        }
    }

    public function stepsTestData()
    {
        $good = get_class(\Mockery::mock(StepInterface::class));

        return [
            [1, null], // not arrays are skipped
            ['foo', null], // not arrays are skipped
            [true, null], // not arrays are skipped
            [[], null], // empty arrays are skipped,
            [['foo', 'bar', 'baz'], null], // arrays of invalid values are skipped
            [[$good, StepInterface::class], [$good]] // interface skipped, can't be instantiated
        ];
    }

    /**
     * @dataProvider stepsScriptData
     * @param mixed $argument
     * @param array|null $expected
     */
    public function testValidateScripts($argument, $expected)
    {
        $config = new Config(['scripts' => $argument]);

        if (is_null($expected)) {
            assertArrayNotHasKey('scripts', $config);
        } else {
            assertSame($expected, $config['scripts']);
        }
    }

    public function stepsScriptData()
    {
        return [
            [1, null], // not callbale are skipped
            ['foo', null], // not callbale are skipped
            [true, null], // not callbale are skipped
            [[], null], // empty arrays are skipped,
            [['foo' => 'is_string', 'bar' => 'is_int'], null], // invalid names are skipped
            [['foo' => 'is_string', 'pre-foo' => 'is_int'], ['pre-foo' => ['is_int']]],
            [
                ['pre-foo' => 'is_string', 'post-foo' => 'is_int'],
                ['pre-foo' => ['is_string'], 'post-foo' => ['is_int']], // each script name always has array of scripts
            ],
            [
                ['pre-foo' => ['is_string', 'is_int', 'is_bool']],
                ['pre-foo' => ['is_string', 'is_int', 'is_bool']], // each script name always has array of scripts
            ]
        ];
    }

    /**
     * @dataProvider devOperationScriptData
     * @param mixed $argument
     * @param array|null $expected
     */
    public function testValidateDevOperation($argument, $expected)
    {
        $config = new Config(['content-dev-op' => $argument]);
        // invalid value, which returns null, are then set to default
        is_null($expected) and $expected = $this->defaults['content-dev-op'];

        assertSame($expected, $config['content-dev-op']);
    }

    public function devOperationScriptData()
    {
        return [
            [true, null], // true-alike becomes null to be set as default
            [1, null], // true-alike becomes null to be set as default
            ["true", null], // true-alike becomes null to be set as default
            ["1", null], // true-alike becomes null to be set as default
            ["yes", null], // true-alike becomes null to be set as default
            ["on", null], // true-alike becomes null to be set as default
            ["no", false], // false-alike becomes false
            ["off", false], // false-alike becomes false
            [false, false], // false-alike becomes false
            ["false", false], // false-alike becomes false
            [0, false], // false-alike becomes false
            ["0", false], // false-alike becomes false,
            ['symlink', 'symlink'], // case-insensitive, trim spaces
            ['Symlink ', 'symlink'], // case-insensitive, trim spaces
            [' SIMLINK ', 'symlink'], // case-insensitive, trim spaces
            ['copy', 'copy'], // case-insensitive, trim spaces
            ['Copy ', 'copy'], // case-insensitive, trim spaces
            [' COPY ', 'copy'], // case-insensitive, trim spaces
            ['copy', 'copy'], // case-insensitive, trim spaces
            ['Copy ', 'copy'], // case-insensitive, trim spaces
            [' COPY ', 'copy'], // case-insensitive, trim spaces
            ['ask', 'ask'], // ask-alike, are "ask"
            ['Prompt', 'ask'], // ask-alike, are "ask"
            ['QUERY', 'ask'], // ask-alike, are "ask"
            ['intErrogatE', 'ask'], // ask-alike, are "ask"
            [' DEMAnd ', 'ask'], // ask-alike, are "ask"
            [12, null], // other things, are null
            [[], null], // other things, are null,
            ['foo', null], // other things, are null
            [new \stdClass(), null], // other things, are null
        ];
    }
}
