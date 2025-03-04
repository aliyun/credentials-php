<?php

namespace AlibabaCloud\Credentials\Tests\Unit\Providers;

use AlibabaCloud\Configure\Config;
use AlibabaCloud\Credentials\Credentials;
use AlibabaCloud\Credentials\Tests\Unit\Ini\VirtualRsaKeyPairCredential;
use AlibabaCloud\Credentials\Providers\RsaKeyPairCredentialsProvider;
use RuntimeException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class RsaKeyPairCredentialsProviderTest extends TestCase
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
        $reflection = new ReflectionClass(RsaKeyPairCredentialsProvider::class);
        $privateProperty = $reflection->getProperty($field);
        $privateProperty->setAccessible(true);
        return $privateProperty->getValue($instance);
    }

    public function testConstruct()
    {
        // Setup
        $url = VirtualRsaKeyPairCredential::badPrivateKey();
        $params = [
            'publicKeyId' => 'test',
            'privateKeyFile' => $url,
            'stsEndpoint' => 'sts.' . Config:: ENDPOINT_SUFFIX,
            'durationSeconds' => 6000,
        ];
        $config = [
            'connectTimeout' => 10,
            'readTimeout' => 10,
        ];
        

        $provider = new RsaKeyPairCredentialsProvider($params, $config);

        self::assertEquals('rsa_key_pair', $provider->getProviderName());
        self::assertEquals('rsa_key_pair#publicKeyId#test', $provider->key());
        self::assertEquals(file_get_contents($url), $provider->getPrivateKey());
        self::assertEquals('test', $provider->getPublicKeyId());
        $stsEndpoint = $this->getPrivateField($provider, 'stsEndpoint');
        $durationSeconds = $this->getPrivateField($provider, 'durationSeconds');
        self::assertEquals('sts.' . Config:: ENDPOINT_SUFFIX, $stsEndpoint);
        self::assertEquals(6000, $durationSeconds);

        $params = [
            'publicKeyId' => 'test',
            'privateKeyFile' => $url,
        ];
        $provider = new RsaKeyPairCredentialsProvider($params, $config);

        self::assertEquals('rsa_key_pair', $provider->getProviderName());
        self::assertEquals('rsa_key_pair#publicKeyId#test', $provider->key());
        self::assertEquals(file_get_contents($url), $provider->getPrivateKey());
        self::assertEquals('test', $provider->getPublicKeyId());
        $stsEndpoint = $this->getPrivateField($provider, 'stsEndpoint');
        $durationSeconds = $this->getPrivateField($provider, 'durationSeconds');
        self::assertEquals('sts.ap-northeast-1.' . Config:: ENDPOINT_SUFFIX, $stsEndpoint);
        self::assertEquals(3600, $durationSeconds);

    }

    public function testConstructErrorPublicKeyId()
    {
        // Setup
        $params = [];
        $config = [
            'connectTimeout' => 10,
            'readTimeout' => 10,
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('publicKeyId must be a string');
        new RsaKeyPairCredentialsProvider($params, $config);
    }

    public function testConstructErrorPrivateKeyFile()
    {
        // Setup
        $params = [
            'publicKeyId' => 'test',
        ];
        $config = [
            'connectTimeout' => 10,
            'readTimeout' => 10,
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('privateKeyFile must be a string');
        new RsaKeyPairCredentialsProvider($params, $config);
    }

    public function testConstructErrorDurationSeconds()
    {
        // Setup
        $url = VirtualRsaKeyPairCredential::badPrivateKey();
        $params = [
            'publicKeyId' => 'test',
            'privateKeyFile' => $url,
            'durationSeconds' => 800,
        ];
        $config = [
            'connectTimeout' => 10,
            'readTimeout' => 10,
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Role session expiration should be in the range of 900s - max session duration');
        new RsaKeyPairCredentialsProvider($params, $config);
    }

    public function testSts()
    {
        $url = VirtualRsaKeyPairCredential::privateKeyFileUrl();
        $params = [
            'publicKeyId' => 'testSts',
            'privateKeyFile' => $url,
        ];
        $provider = new RsaKeyPairCredentialsProvider($params);

        $result = '{
    "RequestId": "F702286E-F231-4F40-BB86-XXXXXX",
    "SessionAccessKey": {
        "SessionAccessKeyId": "foo",
        "Expiration": "2049-10-01T07:02:36.225Z",
        "SessionAccessKeySecret": "bar"
    }
}';
        Credentials::mockResponse(200, [], $result);
        $credential = $provider->getCredentials();

        self::assertEquals('foo', $credential->getAccessKeyId());
        self::assertEquals('bar', $credential->getAccessKeySecret());
        self::assertEquals('', $credential->getSecurityToken());
        self::assertEquals('rsa_key_pair', $credential->getProviderName());

        $credential = $provider->getCredentials();
        self::assertEquals('foo', $credential->getAccessKeyId());
        self::assertEquals('bar', $credential->getAccessKeySecret());
        self::assertEquals('', $credential->getSecurityToken());
        self::assertEquals('rsa_key_pair', $credential->getProviderName());
    }

    public function testStsIncomplete()
    {
        // Setup
        $url = VirtualRsaKeyPairCredential::privateKeyFileUrl();
        $params = [
            'publicKeyId' => 'test',
            'privateKeyFile' => $url,
        ];
        $provider = new RsaKeyPairCredentialsProvider($params);

        $result = '{
    "RequestId": "F702286E-F231-4F40-BB86-XXXXXX",
    "SessionAccessKey": {
        "SessionAccessKeyId": "TMPSK.**************",
        "Expiration": "2023-02-19T07:02:36.225Z"
    }
}';
        Credentials::mockResponse(200, [], $result);

        $this->expectException(RuntimeException::class);
        if (method_exists($this, 'expectExceptionMessageMatches')) {
            $this->expectExceptionMessageMatches('/Error retrieving credentials from RsaKeyPair/');
        } elseif (method_exists($this, 'expectExceptionMessageRegExp')) {
            $this->expectExceptionMessageRegExp('/Error retrieving credentials from RsaKeyPair/');
        }
        // Test
        $provider->getCredentials();
    }

    public function testSts500()
    {
        // Setup
        $url = VirtualRsaKeyPairCredential::privateKeyFileUrl();
        $params = [
            'publicKeyId' => 'test',
            'privateKeyFile' => $url,
        ];
        $provider = new RsaKeyPairCredentialsProvider($params);
        Credentials::mockResponse(500, [], 'Error');

        $this->expectException(RuntimeException::class);
        if (method_exists($this, 'expectExceptionMessageMatches')) {
            $this->expectExceptionMessageMatches('/Error refreshing credentials from RsaKeyPair, statusCode: 500, result: Error/');
        } elseif (method_exists($this, 'expectExceptionMessageRegExp')) {
            $this->expectExceptionMessageRegExp('/Error refreshing credentials from RsaKeyPair, statusCode: 500, result: Error/');
        }
        // Test
        $provider->getCredentials();
    }

}
