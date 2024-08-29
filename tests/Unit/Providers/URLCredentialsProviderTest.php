<?php

namespace AlibabaCloud\Credentials\Tests\Unit\Providers;

use AlibabaCloud\Credentials\Credentials;
use AlibabaCloud\Credentials\Providers\URLCredentialsProvider;
use RuntimeException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Class URLCredentialsProviderTest
 *
 * @package AlibabaCloud\Credentials\Tests\Unit\Providers
 */
class URLCredentialsProviderTest extends TestCase
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
        $reflection = new ReflectionClass(URLCredentialsProvider::class);
        $privateProperty = $reflection->getProperty($field);
        $privateProperty->setAccessible(true);
        return $privateProperty->getValue($instance);
    }

    public function testConstruct()
    {
        // Setup
        $params = [
            'credentialsURI' => 'http://credentials.aliyun.com',
        ];
        $config = [
            'connectTimeout' => 10,
            'readTimeout' => 10,
        ];
        putenv("ALIBABA_CLOUD_CREDENTIALS_URI=http://test.aliyun.com");

        $provider = new URLCredentialsProvider($params, $config);

        self::assertEquals('credential_uri', $provider->getProviderName());
        self::assertEquals('credential_uri#http://credentials.aliyun.com', $provider->key());
        $credentialsURI = $this->getPrivateField($provider, 'credentialsURI');
        self::assertEquals('http://credentials.aliyun.com', $credentialsURI);

        $provider = new URLCredentialsProvider([], $config);
        self::assertEquals('credential_uri', $provider->getProviderName());
        self::assertEquals('credential_uri#http://test.aliyun.com', $provider->key());
        $credentialsURI = $this->getPrivateField($provider, 'credentialsURI');
        self::assertEquals('http://test.aliyun.com', $credentialsURI);

        putenv("ALIBABA_CLOUD_CREDENTIALS_URI=");
    }

    public function testConstructError()
    {
        // Setup
        $params = [];
        $config = [
            'connectTimeout' => 10,
            'readTimeout' => 10,
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('credentialsURI must be a string');
        new URLCredentialsProvider($params, $config);
    }

    public function testSts()
    {
        $params = [
            'credentialsURI' => 'http://credentials.aliyun.com',
        ];
        $provider = new URLCredentialsProvider($params);

        $result = '{
        "AccessKeySecret": "bar",
        "AccessKeyId": "foo",
        "Expiration": "2049-10-25T03:56:19Z",
        "SecurityToken": "token"
    }';
        Credentials::mockResponse(200, [], $result);
        $credential = $provider->getCredentials();

        self::assertEquals('foo', $credential->getAccessKeyId());
        self::assertEquals('bar', $credential->getAccessKeySecret());
        self::assertEquals('token', $credential->getSecurityToken());

        $credential = $provider->getCredentials();
        self::assertEquals('foo', $credential->getAccessKeyId());
        self::assertEquals('bar', $credential->getAccessKeySecret());
        self::assertEquals('token', $credential->getSecurityToken());
    }

    public function testStsIncomplete()
    {
        // Setup
        $params = [
            'credentialsURI' => 'test',
        ];
        $provider = new URLCredentialsProvider($params);

        $result = '{
        "AccessKeySecret": "bar",
        "AccessKeyId": "foo",
        "SecurityToken": "token"
    }';
        Credentials::mockResponse(200, [], $result);

        $this->expectException(RuntimeException::class);
        if (method_exists($this, 'expectExceptionMessageMatches')) {
            $this->expectExceptionMessageMatches('/Error retrieving credentials from credentialsURI/');
        } elseif (method_exists($this, 'expectExceptionMessageRegExp')) {
            $this->expectExceptionMessageRegExp('/Error retrieving credentials from credentialsURI/');
        }
        // Test
        $provider->getCredentials();
    }

    public function testSts500()
    {
        // Setup
        $params = [
            'credentialsURI' => 'test',
        ];
        $provider = new URLCredentialsProvider($params);
        Credentials::mockResponse(500, [], 'Error');

        $this->expectException(RuntimeException::class);
        if (method_exists($this, 'expectExceptionMessageMatches')) {
            $this->expectExceptionMessageMatches('/Error refreshing credentials from credentialsURI, statusCode: 500, result: Error/');
        } elseif (method_exists($this, 'expectExceptionMessageRegExp')) {
            $this->expectExceptionMessageRegExp('/Error refreshing credentials from credentialsURI, statusCode: 500, result: Error/');
        }
        // Test
        $provider->getCredentials();
    }
}
