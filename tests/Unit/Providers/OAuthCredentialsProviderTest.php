<?php

namespace AlibabaCloud\Credentials\Tests\Unit\Providers;

use AlibabaCloud\Credentials\Credentials;
use AlibabaCloud\Credentials\Providers\OAuthCredentialsProvider;
use InvalidArgumentException;
use RuntimeException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Class OAuthCredentialsProviderTest
 *
 * @package AlibabaCloud\Credentials\Tests\Unit\Providers
 */
class OAuthCredentialsProviderTest extends TestCase
{
    /**
     * @before
     */
    protected function initialize()
    {
        parent::setUp();
        Credentials::cancelMock();
    }

    private function getPrivateField($instance, $field)
    {
        $reflection = new ReflectionClass(OAuthCredentialsProvider::class);
        $privateProperty = $reflection->getProperty($field);
        $privateProperty->setAccessible(true);
        return $privateProperty->getValue($instance);
    }

    public function testConstructEmptyClientId()
    {
        $this->expectException(InvalidArgumentException::class);
        if (method_exists($this, 'expectExceptionMessageMatches')) {
            $this->expectExceptionMessageMatches('/The clientId is empty/');
        } elseif (method_exists($this, 'expectExceptionMessageRegExp')) {
            $this->expectExceptionMessageRegExp('/The clientId is empty/');
        }

        new OAuthCredentialsProvider([
            'clientId' => '',
            'signInUrl' => 'https://oauth.aliyun.com',
        ]);
    }

    public function testConstructEmptySignInUrl()
    {
        $this->expectException(InvalidArgumentException::class);
        if (method_exists($this, 'expectExceptionMessageMatches')) {
            $this->expectExceptionMessageMatches('/The url for sign-in is empty/');
        } elseif (method_exists($this, 'expectExceptionMessageRegExp')) {
            $this->expectExceptionMessageRegExp('/The url for sign-in is empty/');
        }

        new OAuthCredentialsProvider([
            'clientId' => 'clientId',
            'signInUrl' => '',
        ]);
    }

    public function testConstructSuccess()
    {
        $provider = new OAuthCredentialsProvider([
            'clientId' => 'myClientId',
            'signInUrl' => 'https://oauth.aliyun.com',
            'refreshToken' => 'refreshToken',
            'accessToken' => 'accessToken',
            'accessTokenExpire' => time() + 5000,
        ]);

        $clientId = $this->getPrivateField($provider, 'clientId');
        $signInUrl = $this->getPrivateField($provider, 'signInUrl');
        $refreshToken = $this->getPrivateField($provider, 'refreshToken');

        self::assertEquals('myClientId', $clientId);
        self::assertEquals('https://oauth.aliyun.com', $signInUrl);
        self::assertEquals('refreshToken', $refreshToken);
        self::assertEquals('oauth', $provider->getProviderName());
        self::assertEquals('oauth#https://oauth.aliyun.com#myClientId', $provider->key());
    }

    public function testConstructWithOptions()
    {
        $provider = new OAuthCredentialsProvider([
            'clientId' => 'clientId',
            'signInUrl' => 'https://oauth.aliyun.com',
        ], [
            'connectTimeout' => 15,
            'readTimeout' => 20,
        ]);

        $connectTimeout = $this->getPrivateField($provider, 'connectTimeout');
        $readTimeout = $this->getPrivateField($provider, 'readTimeout');
        self::assertEquals(15, $connectTimeout);
        self::assertEquals(20, $readTimeout);
    }

    public function testGetCredentialsSuccess()
    {
        $result = '{"AccessKeyId":"ak","AccessKeySecret":"sk","SecurityToken":"token","Expiration":"2049-10-20T04:27:09Z"}';
        Credentials::mockResponse(200, [], $result);

        $provider = new OAuthCredentialsProvider([
            'clientId' => 'clientId',
            'signInUrl' => 'https://oauth.aliyun.com',
            'refreshToken' => 'refreshToken',
            'accessToken' => 'accessToken',
            'accessTokenExpire' => time() + 5000,
        ]);

        $cred = $provider->getCredentials();
        self::assertEquals('ak', $cred->getAccessKeyId());
        self::assertEquals('sk', $cred->getAccessKeySecret());
        self::assertEquals('token', $cred->getSecurityToken());
        self::assertEquals('oauth', $cred->getProviderName());

        $cred = $provider->getCredentials();
        self::assertEquals('ak', $cred->getAccessKeyId());
        self::assertEquals('sk', $cred->getAccessKeySecret());
        self::assertEquals('oauth', $cred->getProviderName());
    }

    public function testGetCredentialsCamelCaseFallback()
    {
        $result = '{"accessKeyId":"ak","accessKeySecret":"sk","securityToken":"token","expiration":"2049-10-20T04:27:09Z"}';
        Credentials::mockResponse(200, [], $result);

        $provider = new OAuthCredentialsProvider([
            'clientId' => 'clientId-camel',
            'signInUrl' => 'https://oauth.aliyun.com',
            'refreshToken' => 'refreshToken',
            'accessToken' => 'accessToken',
            'accessTokenExpire' => time() + 5000,
        ]);

        $cred = $provider->getCredentials();
        self::assertEquals('ak', $cred->getAccessKeyId());
        self::assertEquals('sk', $cred->getAccessKeySecret());
        self::assertEquals('token', $cred->getSecurityToken());
    }

    public function testGetExchangeFieldPrefersPascalCase()
    {
        self::assertEquals('p', OAuthCredentialsProvider::getExchangeField(
            ['AccessKeyId' => 'p', 'accessKeyId' => 'c'],
            'AccessKeyId',
            'accessKeyId'
        ));
        self::assertEquals('c', OAuthCredentialsProvider::getExchangeField(
            ['accessKeyId' => 'c'],
            'AccessKeyId',
            'accessKeyId'
        ));
        self::assertNull(OAuthCredentialsProvider::getExchangeField([], 'AccessKeyId', 'accessKeyId'));
        self::assertNull(OAuthCredentialsProvider::getExchangeField(null, 'AccessKeyId', 'accessKeyId'));
        self::assertEquals('', OAuthCredentialsProvider::getExchangeField(
            ['AccessKeyId' => ''],
            'AccessKeyId',
            'accessKeyId'
        ));
    }

    public function testGetCredentials500Error()
    {
        Credentials::mockResponse(500, [], 'Server Error');

        $provider = new OAuthCredentialsProvider([
            'clientId' => 'clientId-500',
            'signInUrl' => 'https://oauth.aliyun.com',
            'refreshToken' => 'refreshToken',
            'accessToken' => 'accessToken',
            'accessTokenExpire' => time() + 5000,
        ]);

        $this->expectException(RuntimeException::class);
        if (method_exists($this, 'expectExceptionMessageMatches')) {
            $this->expectExceptionMessageMatches('/Get session token from OAuth failed, statusCode: 500/');
        } elseif (method_exists($this, 'expectExceptionMessageRegExp')) {
            $this->expectExceptionMessageRegExp('/Get session token from OAuth failed, statusCode: 500/');
        }

        $provider->getCredentials();
    }

    public function testGetCredentialsMissingFields()
    {
        $result = '{"accessKeyId":"","accessKeySecret":"","securityToken":""}';
        Credentials::mockResponse(200, [], $result);

        $provider = new OAuthCredentialsProvider([
            'clientId' => 'clientId-missing',
            'signInUrl' => 'https://oauth.aliyun.com',
            'refreshToken' => 'refreshToken',
            'accessToken' => 'accessToken',
            'accessTokenExpire' => time() + 5000,
        ]);

        $this->expectException(RuntimeException::class);
        if (method_exists($this, 'expectExceptionMessageMatches')) {
            $this->expectExceptionMessageMatches('/Refresh session token from OAuth failed, fail to get credentials/');
        } elseif (method_exists($this, 'expectExceptionMessageRegExp')) {
            $this->expectExceptionMessageRegExp('/Refresh session token from OAuth failed, fail to get credentials/');
        }

        $provider->getCredentials();
    }

    public function testTokenRefresh()
    {
        $refreshResponse = '{"access_token":"new_access","refresh_token":"new_refresh","expires_in":3600}';
        $exchangeResponse = '{"AccessKeyId":"ak","AccessKeySecret":"sk","SecurityToken":"token","Expiration":"2049-10-20T04:27:09Z"}';
        Credentials::mockResponse(200, [], $refreshResponse);
        Credentials::mockResponse(200, [], $exchangeResponse);

        $provider = new OAuthCredentialsProvider([
            'clientId' => 'clientId-refresh',
            'signInUrl' => 'https://oauth.aliyun.com',
            'refreshToken' => 'oldRefresh',
            'accessToken' => 'expiredToken',
            'accessTokenExpire' => time() - 100,
        ]);

        $cred = $provider->getCredentials();
        self::assertEquals('ak', $cred->getAccessKeyId());
        self::assertEquals('sk', $cred->getAccessKeySecret());
        self::assertEquals('token', $cred->getSecurityToken());
        self::assertEquals('oauth', $cred->getProviderName());

        $newAccessToken = $this->getPrivateField($provider, 'accessToken');
        $newRefreshToken = $this->getPrivateField($provider, 'refreshToken');
        self::assertEquals('new_access', $newAccessToken);
        self::assertEquals('new_refresh', $newRefreshToken);
    }

    public function testTokenRefreshFailure()
    {
        Credentials::mockResponse(500, [], 'Refresh Error');

        $provider = new OAuthCredentialsProvider([
            'clientId' => 'clientId-refresh-fail',
            'signInUrl' => 'https://oauth.aliyun.com',
            'refreshToken' => 'oldRefresh',
            'accessToken' => 'expiredToken',
            'accessTokenExpire' => time() - 100,
        ]);

        $this->expectException(RuntimeException::class);
        if (method_exists($this, 'expectExceptionMessageMatches')) {
            $this->expectExceptionMessageMatches('/Failed to refresh OAuth token, status code: 500/');
        } elseif (method_exists($this, 'expectExceptionMessageRegExp')) {
            $this->expectExceptionMessageRegExp('/Failed to refresh OAuth token, status code: 500/');
        }

        $provider->getCredentials();
    }

    public function testTokenRefreshMissingFields()
    {
        $refreshResponse = '{"access_token":"","refresh_token":""}';
        Credentials::mockResponse(200, [], $refreshResponse);

        $provider = new OAuthCredentialsProvider([
            'clientId' => 'clientId-refresh-missing',
            'signInUrl' => 'https://oauth.aliyun.com',
            'refreshToken' => 'oldRefresh',
            'accessToken' => 'expiredToken',
            'accessTokenExpire' => time() - 100,
        ]);

        $this->expectException(RuntimeException::class);
        if (method_exists($this, 'expectExceptionMessageMatches')) {
            $this->expectExceptionMessageMatches('/Failed to refresh OAuth token/');
        } elseif (method_exists($this, 'expectExceptionMessageRegExp')) {
            $this->expectExceptionMessageRegExp('/Failed to refresh OAuth token/');
        }

        $provider->getCredentials();
    }

    public function testTokenUpdateCallback()
    {
        $callbackInvoked = false;
        $capturedArgs = [];

        $callback = function () use (&$callbackInvoked, &$capturedArgs) {
            $callbackInvoked = true;
            $capturedArgs = func_get_args();
        };

        $exchangeResponse = '{"AccessKeyId":"ak","AccessKeySecret":"sk","SecurityToken":"token","Expiration":"2049-10-20T04:27:09Z"}';
        Credentials::mockResponse(200, [], $exchangeResponse);

        $provider = new OAuthCredentialsProvider([
            'clientId' => 'clientId-callback',
            'signInUrl' => 'https://oauth.aliyun.com',
            'refreshToken' => 'refreshToken',
            'accessToken' => 'accessToken',
            'accessTokenExpire' => time() + 5000,
            'tokenUpdateCallback' => $callback,
        ]);

        $provider->getCredentials();

        self::assertTrue($callbackInvoked);
        self::assertCount(7, $capturedArgs);
        self::assertEquals('refreshToken', $capturedArgs[0]);
        self::assertEquals('accessToken', $capturedArgs[1]);
        self::assertEquals('ak', $capturedArgs[2]);
        self::assertEquals('sk', $capturedArgs[3]);
        self::assertEquals('token', $capturedArgs[4]);
    }

    public function testGetProviderName()
    {
        $provider = new OAuthCredentialsProvider([
            'clientId' => 'clientId',
            'signInUrl' => 'https://oauth.aliyun.com',
        ]);

        self::assertEquals('oauth', $provider->getProviderName());
    }
}
