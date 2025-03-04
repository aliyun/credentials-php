<?php

namespace AlibabaCloud\Credentials\Tests\Unit\Providers;

use AlibabaCloud\Configure\Config;
use AlibabaCloud\Credentials\Providers\DefaultCredentialsProvider;
use AlibabaCloud\Credentials\Providers\ProfileCredentialsProvider;
use AlibabaCloud\Credentials\Credentials;
use AlibabaCloud\Credentials\Tests\Mock\VirtualFile;
use AlibabaCloud\Credentials\Tests\Unit\Ini\VirtualAccessKeyCredential;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use InvalidArgumentException;

class DefaultCredentialsProviderTest extends TestCase
{
    /**
     * @before
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    protected function initialize()
    {
        parent::setUp();
        Credentials::cancelMock();
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage No providers in chain
     */
    public function testNoCustomProviders()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No providers in chain');
        DefaultCredentialsProvider::set();
    }

    public function testFlushCustomProviders()
    {
        DefaultCredentialsProvider::set(
            new ProfileCredentialsProvider()
        );
        self::assertTrue(DefaultCredentialsProvider::hasCustomChain());
        DefaultCredentialsProvider::flush();
        self::assertFalse(DefaultCredentialsProvider::hasCustomChain());
    }

    public function testGetProviderName()
    {
        $provider = new DefaultCredentialsProvider();
        self::assertEquals('default', $provider->getProviderName());
    }

    public function testDefaultProviderWithEnv()
    {
        putenv(Config:: ENV_PREFIX . "ACCESS_KEY_ID=id");
        putenv(Config:: ENV_PREFIX . "ACCESS_KEY_SECRET=secret");

        $provider = new DefaultCredentialsProvider();
        $credentials = $provider->getCredentials();
        self::assertEquals("id", $credentials->getAccessKeyId());
        self::assertEquals("secret", $credentials->getAccessKeySecret());
        self::assertEquals("default/env", $credentials->getProviderName());

        putenv(Config:: ENV_PREFIX . "SECURITY_TOKEN=token");
        $credentials = $provider->getCredentials();
        self::assertEquals("id", $credentials->getAccessKeyId());
        self::assertEquals("secret", $credentials->getAccessKeySecret());
        self::assertEquals("token", $credentials->getSecurityToken());
        self::assertEquals("default/env", $credentials->getProviderName());

        putenv(Config:: ENV_PREFIX . "ACCESS_KEY_ID=");
        putenv(Config:: ENV_PREFIX . "ACCESS_KEY_SECRET=");
        putenv(Config:: ENV_PREFIX . "SECURITY_TOKEN=");
    }

    public function testDefaultProviderWithOIDC()
    {
        putenv(Config:: ENV_PREFIX . "ROLE_ARN=role-arn");
        putenv(Config:: ENV_PREFIX . "OIDC_PROVIDER_ARN=provider-arn");
        $vf = new VirtualFile("token");
        $url = $vf->url("token-file");
        putenv(Config:: ENV_PREFIX . "OIDC_TOKEN_FILE=$url");

        $provider = new DefaultCredentialsProvider();
        $result = '{
    "RequestId": "88FEA385-EF5D-4A8A-8C00-A07DAE3BFD44",
    "AssumedRoleUser": {
        "AssumedRoleId": "********************",
        "Arn": "********************"
    },
    "Credentials": {
        "AccessKeySecret": "bar",
        "AccessKeyId": "foo",
        "Expiration": "2049-10-25T03:56:19Z",
        "SecurityToken": "token"
    }
}';
        Credentials::mockResponse(200, [], $result);
        $credentials = $provider->getCredentials();
        self::assertEquals("foo", $credentials->getAccessKeyId());
        self::assertEquals("bar", $credentials->getAccessKeySecret());
        self::assertEquals("token", $credentials->getSecurityToken());
        self::assertEquals("default/oidc_role_arn", $credentials->getProviderName());

        putenv(Config:: ENV_PREFIX . "ROLE_ARN=");
        putenv(Config:: ENV_PREFIX . "OIDC_PROVIDER_ARN=");
        putenv(Config:: ENV_PREFIX . "OIDC_TOKEN_FILE=");
    }

    public function testDefaultProviderWithProfile()
    {
        $vf = VirtualAccessKeyCredential::ok();
        putenv(Config:: ENV_PREFIX . "CREDENTIALS_FILE=$vf");
        putenv(Config:: ENV_PREFIX . "PROFILE=ok");
        $provider = new DefaultCredentialsProvider();
        $credentials = $provider->getCredentials();
        self::assertEquals("foo", $credentials->getAccessKeyId());
        self::assertEquals("bar", $credentials->getAccessKeySecret());
        self::assertEquals("default/profile", $credentials->getProviderName());
        putenv(Config:: ENV_PREFIX . "CREDENTIALS_FILE=");
        putenv(Config:: ENV_PREFIX . "PROFILE=");
    }

    public function testDefaultProviderWithIMDS()
    {
        putenv(Config:: ENV_PREFIX . "ECS_METADATA=roleName");

        $provider = new DefaultCredentialsProvider();
        $result = [
            'Expiration'      => '2049-10-01 00:00:00',
            'AccessKeyId'     => 'foo',
            'AccessKeySecret' => 'bar',
            'SecurityToken'   => 'token',
            'Code'            => 'Success',
        ];
        Credentials::mockResponse(200, [], 'Token');
        Credentials::mockResponse(200, [], $result);
        $credentials = $provider->getCredentials();
        self::assertEquals("foo", $credentials->getAccessKeyId());
        self::assertEquals("bar", $credentials->getAccessKeySecret());
        self::assertEquals("token", $credentials->getSecurityToken());
        self::assertEquals("default/ecs_ram_role", $credentials->getProviderName());

        putenv(Config:: ENV_PREFIX . "ECS_METADATA=");
    }

    public function testDefaultProviderWithURI()
    {
        putenv(Config:: ENV_PREFIX . "ECS_METADATA_DISABLED=true");
        putenv(Config:: ENV_PREFIX . "CREDENTIALS_URI=http://localhost:8080/token");

        $provider = new DefaultCredentialsProvider();
        $result = [
            'Expiration'      => '2049-10-01 00:00:00',
            'AccessKeyId'     => 'foo',
            'AccessKeySecret' => 'bar',
            'SecurityToken'   => 'token',
        ];
        Credentials::mockResponse(200, [], $result);
        $credentials = $provider->getCredentials();
        self::assertEquals("foo", $credentials->getAccessKeyId());
        self::assertEquals("bar", $credentials->getAccessKeySecret());
        self::assertEquals("token", $credentials->getSecurityToken());
        self::assertEquals("default/credential_uri", $credentials->getProviderName());

        putenv(Config:: ENV_PREFIX . "CREDENTIALS_URI=");
        putenv(Config:: ENV_PREFIX . "ECS_METADATA_DISABLED=");
    }

    public function testDefaultProviderWithReuseLast()
    {
        // 同时开启OIDC和CredentialsURI
        putenv(Config:: ENV_PREFIX . "ROLE_ARN=test-role-arn");
        putenv(Config:: ENV_PREFIX . "OIDC_PROVIDER_ARN=test-provider-arn");
        $vf = new VirtualFile("token");
        $url = $vf->url("token-file");
        putenv(Config:: ENV_PREFIX . "OIDC_TOKEN_FILE=$url");
        putenv(Config:: ENV_PREFIX . "CREDENTIALS_URI=http://localhost:8080/token");
        putenv(Config:: ENV_PREFIX . "ECS_METADATA_DISABLED=true");

        $provider = new DefaultCredentialsProvider();
        $result = [
            'Expiration'      => '2049-10-01 00:00:00',
            'AccessKeyId'     => 'foo',
            'AccessKeySecret' => 'bar',
            'SecurityToken'   => 'token',
        ];
        Credentials::mockResponse(200, [], $result);
        $credentials = $provider->getCredentials();
        self::assertEquals("foo", $credentials->getAccessKeyId());
        self::assertEquals("bar", $credentials->getAccessKeySecret());
        self::assertEquals("token", $credentials->getSecurityToken());
        self::assertEquals("default/credential_uri", $credentials->getProviderName());

        $result = '{
    "RequestId": "88FEA385-EF5D-4A8A-8C00-A07DAE3BFD44",
    "AssumedRoleUser": {
        "AssumedRoleId": "********************",
        "Arn": "********************"
    },
    "Credentials": {
        "AccessKeySecret": "bar",
        "AccessKeyId": "foo",
        "Expiration": "2049-10-25T03:56:19Z",
        "SecurityToken": "token"
    }
}';
        Credentials::mockResponse(200, [], $result);
        $credentials = $provider->getCredentials();
        self::assertEquals("foo", $credentials->getAccessKeyId());
        self::assertEquals("bar", $credentials->getAccessKeySecret());
        self::assertEquals("token", $credentials->getSecurityToken());
        self::assertEquals("default/credential_uri", $credentials->getProviderName());

        putenv(Config:: ENV_PREFIX . "CREDENTIALS_URI=");
        putenv(Config:: ENV_PREFIX . "ROLE_ARN=");
        putenv(Config:: ENV_PREFIX . "OIDC_PROVIDER_ARN=");
        putenv(Config:: ENV_PREFIX . "OIDC_TOKEN_FILE=");
        putenv(Config:: ENV_PREFIX . "ECS_METADATA_DISABLED=");
    }

    public function testDefaultProviderWithUnReuseLast()
    {
        // 同时开启OIDC和CredentialsURI
        putenv(Config:: ENV_PREFIX . "ROLE_ARN=test-role-arn");
        putenv(Config:: ENV_PREFIX . "OIDC_PROVIDER_ARN=test-provider-arn");
        $vf = new VirtualFile("token");
        $url = $vf->url("token-file");
        putenv(Config:: ENV_PREFIX . "OIDC_TOKEN_FILE=$url");
        putenv(Config:: ENV_PREFIX . "CREDENTIALS_URI=http://localhost:8080/token");
        putenv(Config:: ENV_PREFIX . "ECS_METADATA_DISABLED=true");

        $provider = new DefaultCredentialsProvider([
            'reuseLastProviderEnabled' => false,
        ]);
        $result = [
            'Expiration'      => '2049-10-01 00:00:00',
            'AccessKeyId'     => 'foo',
            'AccessKeySecret' => 'bar',
            'SecurityToken'   => 'token',
        ];
        Credentials::mockResponse(200, [], $result);
        $result = '{
    "RequestId": "88FEA385-EF5D-4A8A-8C00-A07DAE3BFD44",
    "AssumedRoleUser": {
        "AssumedRoleId": "********************",
        "Arn": "********************"
    },
    "Credentials": {
        "AccessKeySecret": "bar",
        "AccessKeyId": "foo",
        "Expiration": "2049-10-25T03:56:19Z",
        "SecurityToken": "token"
    }
}';
        Credentials::mockResponse(200, [], $result);
        $credentials = $provider->getCredentials();
        self::assertEquals("foo", $credentials->getAccessKeyId());
        self::assertEquals("bar", $credentials->getAccessKeySecret());
        self::assertEquals("token", $credentials->getSecurityToken());
        self::assertEquals("default/credential_uri", $credentials->getProviderName());

        $credentials = $provider->getCredentials();
        self::assertEquals("foo", $credentials->getAccessKeyId());
        self::assertEquals("bar", $credentials->getAccessKeySecret());
        self::assertEquals("token", $credentials->getSecurityToken());
        self::assertEquals("default/oidc_role_arn", $credentials->getProviderName());

        putenv(Config:: ENV_PREFIX . "CREDENTIALS_URI=");
        putenv(Config:: ENV_PREFIX . "ROLE_ARN=");
        putenv(Config:: ENV_PREFIX . "OIDC_PROVIDER_ARN=");
        putenv(Config:: ENV_PREFIX . "OIDC_TOKEN_FILE=");
        putenv(Config:: ENV_PREFIX . "ECS_METADATA_DISABLED=");
    }
}
