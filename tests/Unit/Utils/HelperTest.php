<?php

namespace AlibabaCloud\Credentials\Tests\Unit;

use AlibabaCloud\Credentials\Credential;
use AlibabaCloud\Credentials\Utils\Helper;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;

/**
 * Class HelperTest
 *
 * @package AlibabaCloud\Credentials\Tests\Unit
 */
class HelperTest extends TestCase
{
    public static function testDefault()
    {
        self::assertEquals('default', Helper::env('default', 'default'));
    }

    public static function testEnv()
    {
        self::assertEquals(null, Helper::env('null'));
    }

    public static function testSwitch()
    {
        putenv('TRUE=true');
        self::assertEquals('true', getenv('TRUE'));
        self::assertEquals(true, Helper::env('TRUE'));
        putenv('TRUE=(true)');
        self::assertEquals('(true)', getenv('TRUE'));
        self::assertEquals(true, Helper::env('TRUE'));

        putenv('FALSE=false');
        self::assertEquals('false', getenv('FALSE'));
        self::assertEquals(false, Helper::env('FALSE'));
        putenv('FALSE=(false)');
        self::assertEquals('(false)', getenv('FALSE'));
        self::assertEquals(false, Helper::env('FALSE'));

        putenv('EMPTY=empty');
        self::assertEquals('empty', getenv('EMPTY'));
        self::assertEquals(false, Helper::env('EMPTY'));
        putenv('EMPTY=(empty)');
        self::assertEquals('(empty)', getenv('EMPTY'));
        self::assertEquals('', Helper::env('EMPTY'));

        putenv('NULL=null');
        self::assertEquals('null', getenv('NULL'));
        self::assertEquals(null, Helper::env('NULL'));
        putenv('NULL=(null)');
        self::assertEquals('(null)', getenv('NULL'));
        self::assertEquals(null, Helper::env('NULL'));
    }

    public static function testString()
    {
        putenv('STRING="Alibaba Cloud"');
        self::assertEquals('"Alibaba Cloud"', getenv('STRING'));
        self::assertEquals('Alibaba Cloud', Helper::env('STRING'));

        putenv('STRING="Alibaba Cloud');
        self::assertEquals('"Alibaba Cloud', getenv('STRING'));
        self::assertEquals('"Alibaba Cloud', Helper::env('STRING'));
    }

    public static function testEnvNotEmpty()
    {
        self::assertFalse(Helper::envNotEmpty('ALIBABA_CLOUD_NOT_EXISTS'));
    }

    public static function testEnvNotEmptyException()
    {
        putenv('ALIBABA_CLOUD_NOT_EXISTS=');

        self::assertFalse(Helper::envNotEmpty('ALIBABA_CLOUD_NOT_EXISTS'));
    }

    public static function testInOpenBaseDir()
    {
        if (Helper::isWindows()) {
            $dirs = 'C:\\projects;C:\\Users';
            ini_set('open_basedir', $dirs);
            self::assertEquals($dirs, ini_get('open_basedir'));
        } else {
            $dirs = 'vfs://AlibabaCloud:/home:/Users:/private:/a/b';
            ini_set('open_basedir', $dirs);
            self::assertEquals($dirs, ini_get('open_basedir'));
            self::assertTrue(Helper::inOpenBasedir('/Users/alibabacloud'));
            self::assertTrue(Helper::inOpenBasedir('/private/alibabacloud'));
            self::assertFalse(Helper::inOpenBasedir('/no/permission'));
            self::assertFalse(Helper::inOpenBasedir('/a'));
            self::assertTrue(Helper::inOpenBasedir('/a/b/'));
            self::assertTrue(Helper::inOpenBasedir('/a/b/c'));
            self::assertFalse(Helper::inOpenBasedir('/b'));
            self::assertFalse(Helper::inOpenBasedir('/b/'));
            self::assertFalse(Helper::inOpenBasedir('/x/d/c.txt'));
            self::assertFalse(Helper::inOpenBasedir('/a/b.php'));
        }
        ini_set('open_basedir', null);
    }

    public function testMerge()
    {
        $params = Helper::merge(
            [
                [1 => 'abc'],
                ['a', 'b'],
                [['c', 'd']],
                ['e' => ['a', 'b']],
                ['e' => ['c', 'd']],
            ]
        );

        self::assertEquals(
            [
                0 => 'abc',
                1 => 'a',
                2 => 'b',
                3 => [
                    0 => 'c',
                    1 => 'd',
                ],
                'e' => [
                    0 => 'a',
                    1 => 'b',
                    2 => 'c',
                    3 => 'd',
                ],
            ],
            $params
        );
    }

    /**
     * @throws ReflectionException
     */
    public function testGetsHomeDirectoryForWindowsUser()
    {
        putenv('HOME=');
        putenv('HOMEDRIVE=C:');
        putenv('HOMEPATH=\\Users\\Alibaba');
        $ref = new ReflectionClass(Helper::class);
        $method = $ref->getMethod('getHomeDirectory');
        $method->setAccessible(true);
        $this->assertEquals('C:\\Users\\Alibaba', $method->invoke(null));
    }

    /**
     * @depends testGetsHomeDirectoryForWindowsUser
     * @throws ReflectionException
     */
    public function testGetsHomeDirectoryForLinuxUser()
    {
        putenv('HOME=/root');
        putenv('HOMEDRIVE=');
        putenv('HOMEPATH=');
        $ref = new ReflectionClass(Helper::class);
        $method = $ref->getMethod('getHomeDirectory');
        $method->setAccessible(true);
        $this->assertEquals('/root', $method->invoke(null));
    }

    public function testSnakeToCamelCase()
    {
        self::assertEquals('', Helper::snakeToCamelCase(''));
        self::assertEquals('bearerToken', Helper::snakeToCamelCase('bearer_token'));
        // take care
        self::assertEquals('disableImdsV1', Helper::snakeToCamelCase('disable_imds_v1'));
        self::assertEquals('publicKeyId', Helper::snakeToCamelCase('public_key_id'));
        self::assertEquals('accessKeyId', Helper::snakeToCamelCase('access_key_id'));
    }

    public function testGetUserAgent()
    {
        self::assertStringStartsWith('AlibabaCloud', Helper::getUserAgent());
        self::assertStringEndsWith('Credentials/' . Credential::VERSION . ' TeaDSL/1', Helper::getUserAgent());
    }

    public function testUnsetReturnNull() {
        $params = [
            'key' => 'value',
            'test' => '',
        ];
        self::assertEquals('value', Helper::unsetReturnNull($params, 'key'));
        self::assertEquals('', Helper::unsetReturnNull($params, 'test'));
        self::assertNull(Helper::unsetReturnNull($params, 'access_key_id'));
    }
}
