<?php

namespace AlibabaCloud\Credentials\Tests\Unit\Providers;

use AlibabaCloud\Configure\Config;
use AlibabaCloud\Credentials\Credentials;
use AlibabaCloud\Credentials\Providers\StaticSTSCredentialsProvider;
use AlibabaCloud\Credentials\Providers\StaticAKCredentialsProvider;
use PHPUnit\Framework\TestCase;

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
        putenv(Config:: ENV_PREFIX . "ACCESS_KEY_ID=id");
        putenv(Config:: ENV_PREFIX . "ACCESS_KEY_SECRET=secret");
        putenv(Config:: ENV_PREFIX . "SECURITY_TOKEN=token");

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

        putenv(Config:: ENV_PREFIX . "ACCESS_KEY_ID=");
        putenv(Config:: ENV_PREFIX . "ACCESS_KEY_SECRET=");
        putenv(Config:: ENV_PREFIX . "SECURITY_TOKEN=");
    }
}
