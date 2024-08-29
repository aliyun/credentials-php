<?php

namespace AlibabaCloud\Credentials\Tests\Unit\Providers;

use AlibabaCloud\Credentials\Credentials;
use AlibabaCloud\Credentials\Providers\StaticSTSCredentialsProvider;
use AlibabaCloud\Credentials\Providers\StaticAKCredentialsProvider;
use PHPUnit\Framework\TestCase;

/**
 * Class StaticCredentialsProviderTest
 *
 * @package AlibabaCloud\Credentials\Tests\Unit\Providers
 */
class StaticCredentialsProviderTest extends TestCase
{

    /**
     * @before
     */
    protected function initialize()
    {
        parent::setUp();
        Credentials::cancelMock();
    }

    public function testConstruct()
    {
        // Setup
        $params = [
            'accessKeyId' => 'test',
            'accessKeySecret' => 'test',
            'securityToken' => 'test',
        ];
        putenv("ALIBABA_CLOUD_ACCESS_KEY_ID=id");
        putenv("ALIBABA_CLOUD_ACCESS_KEY_SECRET=secret");
        putenv("ALIBABA_CLOUD_SECURITY_TOKEN=token");

        $provider = new StaticSTSCredentialsProvider($params);
        $credential = $provider->getCredentials();

        self::assertEquals('test', $credential->getAccessKeyId());
        self::assertEquals('test', $credential->getAccessKeySecret());
        self::assertEquals('test', $credential->getSecurityToken());
        self::assertEquals('static_sts', $credential->getProviderName());

        $provider = new StaticAKCredentialsProvider($params);
        $credential = $provider->getCredentials();

        self::assertEquals('test', $credential->getAccessKeyId());
        self::assertEquals('test', $credential->getAccessKeySecret());
        self::assertEquals('', $credential->getSecurityToken());
        self::assertEquals('static_ak', $credential->getProviderName());

        $provider = new StaticSTSCredentialsProvider([]);
        $credential = $provider->getCredentials();

        self::assertEquals('id', $credential->getAccessKeyId());
        self::assertEquals('secret', $credential->getAccessKeySecret());
        self::assertEquals('token', $credential->getSecurityToken());
        self::assertEquals('static_sts', $credential->getProviderName());

        $provider = new StaticAKCredentialsProvider([]);
        $credential = $provider->getCredentials();

        self::assertEquals('id', $credential->getAccessKeyId());
        self::assertEquals('secret', $credential->getAccessKeySecret());
        self::assertEquals('', $credential->getSecurityToken());
        self::assertEquals('static_ak', $credential->getProviderName());

        putenv("ALIBABA_CLOUD_ACCESS_KEY_ID=");
        putenv("ALIBABA_CLOUD_ACCESS_KEY_SECRET=");
        putenv("ALIBABA_CLOUD_SECURITY_TOKEN=");
    }
}
