<?php

namespace AlibabaCloud\Credentials\Tests\Unit\Providers;

use AlibabaCloud\Credentials\Credentials;
use AlibabaCloud\Credentials\Providers\CloudSSOCredentialsProvider;
use InvalidArgumentException;
use RuntimeException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Class CloudSSOCredentialsProviderTest
 *
 * @package AlibabaCloud\Credentials\Tests\Unit\Providers
 */
class CloudSSOCredentialsProviderTest extends TestCase
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
        $reflection = new ReflectionClass(CloudSSOCredentialsProvider::class);
        $privateProperty = $reflection->getProperty($field);
        $privateProperty->setAccessible(true);
        return $privateProperty->getValue($instance);
    }

    public function testConstructEmptyToken()
    {
        $this->expectException(InvalidArgumentException::class);
        if (method_exists($this, 'expectExceptionMessageMatches')) {
            $this->expectExceptionMessageMatches('/CloudSSO access token is empty or expired/');
        } elseif (method_exists($this, 'expectExceptionMessageRegExp')) {
            $this->expectExceptionMessageRegExp('/CloudSSO access token is empty or expired/');
        }

        new CloudSSOCredentialsProvider([
            'signInUrl' => 'https://signin.aliyun.com',
            'accountId' => '123456',
            'accessConfig' => 'config',
            'accessToken' => '',
            'accessTokenExpire' => time() + 1000,
        ]);
    }

    public function testConstructExpiredToken()
    {
        $this->expectException(InvalidArgumentException::class);
        if (method_exists($this, 'expectExceptionMessageMatches')) {
            $this->expectExceptionMessageMatches('/CloudSSO access token is empty or expired/');
        } elseif (method_exists($this, 'expectExceptionMessageRegExp')) {
            $this->expectExceptionMessageRegExp('/CloudSSO access token is empty or expired/');
        }

        new CloudSSOCredentialsProvider([
            'signInUrl' => 'https://signin.aliyun.com',
            'accountId' => '123456',
            'accessConfig' => 'config',
            'accessToken' => 'token',
            'accessTokenExpire' => time() - 100,
        ]);
    }

    public function testConstructMissingSignInUrl()
    {
        $this->expectException(InvalidArgumentException::class);
        if (method_exists($this, 'expectExceptionMessageMatches')) {
            $this->expectExceptionMessageMatches('/CloudSSO sign in url, account id, and access config cannot be empty/');
        } elseif (method_exists($this, 'expectExceptionMessageRegExp')) {
            $this->expectExceptionMessageRegExp('/CloudSSO sign in url, account id, and access config cannot be empty/');
        }

        new CloudSSOCredentialsProvider([
            'signInUrl' => '',
            'accountId' => '123456',
            'accessConfig' => 'config',
            'accessToken' => 'token',
            'accessTokenExpire' => time() + 1000,
        ]);
    }

    public function testConstructMissingAccountId()
    {
        $this->expectException(InvalidArgumentException::class);
        if (method_exists($this, 'expectExceptionMessageMatches')) {
            $this->expectExceptionMessageMatches('/CloudSSO sign in url, account id, and access config cannot be empty/');
        } elseif (method_exists($this, 'expectExceptionMessageRegExp')) {
            $this->expectExceptionMessageRegExp('/CloudSSO sign in url, account id, and access config cannot be empty/');
        }

        new CloudSSOCredentialsProvider([
            'signInUrl' => 'https://signin.aliyun.com',
            'accountId' => '',
            'accessConfig' => 'config',
            'accessToken' => 'token',
            'accessTokenExpire' => time() + 1000,
        ]);
    }

    public function testConstructSuccess()
    {
        $provider = new CloudSSOCredentialsProvider([
            'signInUrl' => 'https://signin.aliyun.com',
            'accountId' => '123456',
            'accessConfig' => 'config-id',
            'accessToken' => 'token',
            'accessTokenExpire' => time() + 1000,
        ]);

        $signInUrl = $this->getPrivateField($provider, 'signInUrl');
        $accountId = $this->getPrivateField($provider, 'accountId');
        $accessConfig = $this->getPrivateField($provider, 'accessConfig');

        self::assertEquals('https://signin.aliyun.com', $signInUrl);
        self::assertEquals('123456', $accountId);
        self::assertEquals('config-id', $accessConfig);
        self::assertEquals('cloud_sso', $provider->getProviderName());
        self::assertEquals('cloud_sso#https://signin.aliyun.com#123456#config-id', $provider->key());
    }

    public function testConstructWithOptions()
    {
        $provider = new CloudSSOCredentialsProvider([
            'signInUrl' => 'https://signin.aliyun.com',
            'accountId' => '123456',
            'accessConfig' => 'config',
            'accessToken' => 'token',
            'accessTokenExpire' => time() + 1000,
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
        $result = '{"CloudCredential":{"AccessKeyId":"ak","AccessKeySecret":"sk","SecurityToken":"token","Expiration":"2049-10-20T04:27:09Z"}}';
        Credentials::mockResponse(200, [], $result);

        $provider = new CloudSSOCredentialsProvider([
            'signInUrl' => 'https://signin.aliyun.com',
            'accountId' => '123456',
            'accessConfig' => 'config',
            'accessToken' => 'token',
            'accessTokenExpire' => time() + 1000,
        ]);

        $cred = $provider->getCredentials();
        self::assertEquals('ak', $cred->getAccessKeyId());
        self::assertEquals('sk', $cred->getAccessKeySecret());
        self::assertEquals('token', $cred->getSecurityToken());
        self::assertEquals('cloud_sso', $cred->getProviderName());

        $cred = $provider->getCredentials();
        self::assertEquals('ak', $cred->getAccessKeyId());
        self::assertEquals('sk', $cred->getAccessKeySecret());
        self::assertEquals('cloud_sso', $cred->getProviderName());
    }

    public function testGetCredentials500Error()
    {
        Credentials::mockResponse(500, [], 'Server Error');

        $provider = new CloudSSOCredentialsProvider([
            'signInUrl' => 'https://signin.aliyun.com',
            'accountId' => '500error',
            'accessConfig' => 'config',
            'accessToken' => 'token',
            'accessTokenExpire' => time() + 1000,
        ]);

        $this->expectException(RuntimeException::class);
        if (method_exists($this, 'expectExceptionMessageMatches')) {
            $this->expectExceptionMessageMatches('/Get session token from CloudSSO failed, statusCode: 500/');
        } elseif (method_exists($this, 'expectExceptionMessageRegExp')) {
            $this->expectExceptionMessageRegExp('/Get session token from CloudSSO failed, statusCode: 500/');
        }

        $provider->getCredentials();
    }

    public function testGetCredentialsMissingCloudCredential()
    {
        $result = '{"other":"value"}';
        Credentials::mockResponse(200, [], $result);

        $provider = new CloudSSOCredentialsProvider([
            'signInUrl' => 'https://signin.aliyun.com',
            'accountId' => 'missing-cred',
            'accessConfig' => 'config',
            'accessToken' => 'token',
            'accessTokenExpire' => time() + 1000,
        ]);

        $this->expectException(RuntimeException::class);
        if (method_exists($this, 'expectExceptionMessageMatches')) {
            $this->expectExceptionMessageMatches('/Get session token from CloudSSO failed, fail to get credentials/');
        } elseif (method_exists($this, 'expectExceptionMessageRegExp')) {
            $this->expectExceptionMessageRegExp('/Get session token from CloudSSO failed, fail to get credentials/');
        }

        $provider->getCredentials();
    }

    public function testGetCredentialsMissingFields()
    {
        $result = '{"CloudCredential":{"AccessKeyId":"ak"}}';
        Credentials::mockResponse(200, [], $result);

        $provider = new CloudSSOCredentialsProvider([
            'signInUrl' => 'https://signin.aliyun.com',
            'accountId' => 'missing-fields',
            'accessConfig' => 'config',
            'accessToken' => 'token',
            'accessTokenExpire' => time() + 1000,
        ]);

        $this->expectException(RuntimeException::class);
        if (method_exists($this, 'expectExceptionMessageMatches')) {
            $this->expectExceptionMessageMatches('/Get session token from CloudSSO failed, fail to get credentials/');
        } elseif (method_exists($this, 'expectExceptionMessageRegExp')) {
            $this->expectExceptionMessageRegExp('/Get session token from CloudSSO failed, fail to get credentials/');
        }

        $provider->getCredentials();
    }

    public function testGetProviderName()
    {
        $provider = new CloudSSOCredentialsProvider([
            'signInUrl' => 'https://signin.aliyun.com',
            'accountId' => '123456',
            'accessConfig' => 'config',
            'accessToken' => 'token',
            'accessTokenExpire' => time() + 1000,
        ]);

        self::assertEquals('cloud_sso', $provider->getProviderName());
    }
}
