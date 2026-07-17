<?php

namespace AlibabaCloud\Credentials\Tests\Unit\Providers;

use AlibabaCloud\Credentials\Providers\ExternalCredentialsProvider;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Class ExternalCredentialsProviderTest
 *
 * @package AlibabaCloud\Credentials\Tests\Unit\Providers
 */
class ExternalCredentialsProviderTest extends TestCase
{
    public function testConstructEmptyProcessCommand()
    {
        $this->expectException(InvalidArgumentException::class);
        if (method_exists($this, 'expectExceptionMessageMatches')) {
            $this->expectExceptionMessageMatches('/process_command is empty/');
        } elseif (method_exists($this, 'expectExceptionMessageRegExp')) {
            $this->expectExceptionMessageRegExp('/process_command is empty/');
        }

        new ExternalCredentialsProvider();
    }

    public function testGetCredentialsAK()
    {
        $provider = new ExternalCredentialsProvider([
            'processCommand' => '/bin/echo \'{"mode":"AK","access_key_id":"ak","access_key_secret":"sk"}\'',
        ]);

        $credential = $provider->getCredentials();
        self::assertEquals('ak', $credential->getAccessKeyId());
        self::assertEquals('sk', $credential->getAccessKeySecret());
        self::assertNull($credential->getSecurityToken());
        self::assertEquals('external', $credential->getProviderName());
    }

    public function testGetCredentialsStsTokenWithCallback()
    {
        $callbackInvoked = false;
        $capturedArgs = [];
        $provider = new ExternalCredentialsProvider([
            'processCommand' => '/bin/echo \'{"mode":"StsToken","access_key_id":"ak","access_key_secret":"sk","sts_token":"token","expiration":"2049-10-20T04:27:09Z"}\'',
            'credentialUpdateCallback' => function () use (&$callbackInvoked, &$capturedArgs) {
                $callbackInvoked = true;
                $capturedArgs = func_get_args();
            },
        ]);

        $credential = $provider->getCredentials();
        self::assertEquals('ak', $credential->getAccessKeyId());
        self::assertEquals('sk', $credential->getAccessKeySecret());
        self::assertEquals('token', $credential->getSecurityToken());
        self::assertTrue($callbackInvoked);
        self::assertEquals('token', $capturedArgs[2]);
        self::assertTrue($capturedArgs[3] > 0);
    }

    public function testRefreshEveryCallWithoutExpiration()
    {
        $callbackCount = 0;
        $provider = new ExternalCredentialsProvider([
            'processCommand' => '/bin/echo \'{"mode":"AK","access_key_id":"ak","access_key_secret":"sk"}\'',
            'credentialUpdateCallback' => function () use (&$callbackCount) {
                $callbackCount++;
            },
        ]);

        $provider->getCredentials();
        $provider->getCredentials();
        self::assertEquals(2, $callbackCount);
    }

    public function testCallbackExceptionIgnored()
    {
        $provider = new ExternalCredentialsProvider([
            'processCommand' => '/bin/echo \'{"mode":"AK","access_key_id":"ak","access_key_secret":"sk"}\'',
            'credentialUpdateCallback' => function () {
                throw new RuntimeException('callback error');
            },
        ]);

        $credential = $provider->getCredentials();
        self::assertEquals('ak', $credential->getAccessKeyId());
    }

    public function testMissingStsToken()
    {
        $provider = new ExternalCredentialsProvider([
            'processCommand' => '/bin/echo \'{"mode":"StsToken","access_key_id":"ak","access_key_secret":"sk"}\'',
        ]);

        $this->expectException(RuntimeException::class);
        if (method_exists($this, 'expectExceptionMessageMatches')) {
            $this->expectExceptionMessageMatches('/invalid StsToken credential response: sts_token is empty/');
        } elseif (method_exists($this, 'expectExceptionMessageRegExp')) {
            $this->expectExceptionMessageRegExp('/invalid StsToken credential response: sts_token is empty/');
        }

        $provider->getCredentials();
    }
}
