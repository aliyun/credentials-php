<?php

namespace AlibabaCloud\Credentials\Tests\Unit\Providers;

use AlibabaCloud\Credentials\Credentials;
use AlibabaCloud\Credentials\Providers\CLIProfileCredentialsProvider;
use AlibabaCloud\Credentials\Tests\Unit\Ini\VirtualCLIConfig;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;
use InvalidArgumentException;

/**
 * Class CLIProfileCredentialsProviderTest
 *
 * @package AlibabaCloud\Credentials\Tests\Unit\Providers
 */
class CLIProfileCredentialsProviderTest extends TestCase
{
    /**
     * @var CLIProfileCredentialsProvider
     */
    protected $provider;

    /**
     * @before
     */
    protected function initialize()
    {
        parent::setUp();
        Credentials::cancelMock();
        $this->provider = new CLIProfileCredentialsProvider();
    }

    private function getPrivateField($instance, $field)
    {
        $reflection = new ReflectionClass(CLIProfileCredentialsProvider::class);
        $privateProperty = $reflection->getProperty($field);
        $privateProperty->setAccessible(true);
        return $privateProperty->getValue($instance);
    }

    /**
     * @throws Exception
     */
    private function invokeProtectedFunc($instance, $method, ...$args)
    {
        $reflection = new ReflectionClass(CLIProfileCredentialsProvider::class);
        $method = $reflection->getMethod($method);
        $method->setAccessible(true);

        $result = $method->invoke($instance, ...$args);
        return $result;
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSharedCloudSSOSessionRefresh()
    {
        $root = realpath(sys_get_temp_dir()) . DIRECTORY_SEPARATOR . uniqid('cloud-sso-', true);
        mkdir($root);
        $config = $root . '/custom.json';
        file_put_contents($config, '{"current":"dev","profiles":[{"name":"dev","mode":"CloudSSO",'
            . '"sso_session":"company","cloud_sso_account_id":"account","cloud_sso_access_config":"role"}]}');
        $namespace = hash('sha256', realpath($config));
        $dir = $root . '/sso/' . $namespace;
        mkdir($dir, 0700, true);
        $session = $dir . '/company.json';

        try {
            $provider = $this->invokeProtectedFunc($this->provider, 'reloadCredentialsProvider', $config, 'dev');
            foreach ([
                '{"name":"wrong","sign_in_url":"https://example.test"}' => 'Invalid CloudSSO session',
                '{"name":"company","sign_in_url":"https://example.test","access_token":"expired","expires_at":1}' => 'expired',
            ] as $contents => $message) {
                file_put_contents($session, $contents);
                try {
                    $provider->refreshCredentials();
                    self::fail('Expected an invalid shared session to be rejected');
                } catch (RuntimeException $exception) {
                    self::assertNotFalse(strpos($exception->getMessage(), $message));
                }
            }
            foreach (['first', 'second'] as $token) {
                file_put_contents($session, json_encode([
                    'name' => 'company', 'sign_in_url' => 'https://example.test/sign-in',
                    'access_token' => $token, 'expires_at' => time() + 300,
                ]));
                Credentials::mockResponse(200, [], '{"CloudCredential":{"AccessKeyId":"ak","AccessKeySecret":"sk",'
                    . '"SecurityToken":"sts","Expiration":"2099-01-01T00:00:00Z"}}');
                $result = $provider->refreshCredentials();
                self::assertSame('ak', $result->credentials()->getAccessKeyId());
                $history = Credentials::getHistroy();
                $request = end($history)['request'];
                self::assertSame('Bearer ' . $token, $request->getHeaderLine('Authorization'));
                self::assertSame(['AccountId' => 'account', 'AccessConfigurationId' => 'role'],
                    json_decode((string) $request->getBody(), true));
            }
            unlink($session);
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Unable to read CloudSSO session');
            $provider->refreshCredentials();
        } finally {
            if (is_file($session)) {
                unlink($session);
            }
            rmdir($dir);
            rmdir($root . '/sso');
            unlink($config);
            rmdir($root);
        }
    }

    public function testConstruct()
    {
        // Setup
        $provider = new CLIProfileCredentialsProvider();

        $profileName = $this->getPrivateField($provider, 'profileName');
        self::assertNull($profileName);

        $params = [
            'profileName' => 'test',
        ];
        putenv("ALIBABA_CLOUD_PROFILE=profileName");

        $provider = new CLIProfileCredentialsProvider($params);

        $profileName = $this->getPrivateField($provider, 'profileName');

        self::assertEquals('test', $profileName);
        self::assertEquals('cli_profile', $provider->getProviderName());

        putenv("ALIBABA_CLOUD_PROFILE=");
    }

    public function testEmpty()
    {
        $vf = VirtualCLIConfig::emptyContent();
        $provider = new CLIProfileCredentialsProvider();

        $this->expectException(RuntimeException::class);
        if (method_exists($this, 'expectExceptionMessageMatches')) {
            $this->expectExceptionMessageMatches('/Failed to get credential from CLI credentials file/');
        } elseif (method_exists($this, 'expectExceptionMessageRegExp')) {
            $this->expectExceptionMessageRegExp('/Failed to get credential from CLI credentials file/');
        }

        $this->invokeProtectedFunc($provider, 'reloadCredentialsProvider', $vf, 'AK');
    }

    public function testBadFormat()
    {
        $vf = VirtualCLIConfig::badFormat();
        $provider = new CLIProfileCredentialsProvider();

        $this->expectException(RuntimeException::class);
        if (method_exists($this, 'expectExceptionMessageMatches')) {
            $this->expectExceptionMessageMatches('/Failed to get credential from CLI credentials file/');
        } elseif (method_exists($this, 'expectExceptionMessageRegExp')) {
            $this->expectExceptionMessageRegExp('/Failed to get credential from CLI credentials file/');
        }

        $this->invokeProtectedFunc($provider, 'reloadCredentialsProvider', $vf, 'AK');
    }

    public function testInvalidMode()
    {
        $vf = VirtualCLIConfig::noMode();
        $provider = new CLIProfileCredentialsProvider();

        $this->expectException(RuntimeException::class);
        if (method_exists($this, 'expectExceptionMessageMatches')) {
            $this->expectExceptionMessageMatches('/Unsupported credential mode from CLI credentials file/');
        } elseif (method_exists($this, 'expectExceptionMessageRegExp')) {
            $this->expectExceptionMessageRegExp('/Unsupported credential mode from CLI credentials file/');
        }

        $this->invokeProtectedFunc($provider, 'reloadCredentialsProvider', $vf, 'AK');
    }

    public function testNoName()
    {
        $vf = VirtualCLIConfig::noName();
        $provider = new CLIProfileCredentialsProvider();

        $this->expectException(RuntimeException::class);
        if (method_exists($this, 'expectExceptionMessageMatches')) {
            $this->expectExceptionMessageMatches('/Failed to get credential from CLI credentials file/');
        } elseif (method_exists($this, 'expectExceptionMessageRegExp')) {
            $this->expectExceptionMessageRegExp('/Failed to get credential from CLI credentials file/');
        }

        $this->invokeProtectedFunc($provider, 'reloadCredentialsProvider', $vf, 'AK');
    }

    public function testAK()
    {
        $vf = VirtualCLIConfig::full();
        $provider = new CLIProfileCredentialsProvider();
        $credentialsProvider = $this->invokeProtectedFunc($provider, 'reloadCredentialsProvider', $vf, '');
        $credentials = $credentialsProvider->getCredentials();
        self::assertEquals('access_key_id', $credentials->getAccessKeyId());
        self::assertEquals('access_key_secret', $credentials->getAccessKeySecret());
        self::assertEquals('static_ak', $credentials->getProviderName());
    }

    public function testSTS()
    {
        $vf = VirtualCLIConfig::full();
        $provider = new CLIProfileCredentialsProvider();
        $credentialsProvider = $this->invokeProtectedFunc($provider, 'reloadCredentialsProvider', $vf, 'StsToken');
        $credentials = $credentialsProvider->getCredentials();
        self::assertEquals('access_key_id', $credentials->getAccessKeyId());
        self::assertEquals('access_key_secret', $credentials->getAccessKeySecret());
        self::assertEquals('sts_token', $credentials->getSecurityToken());
        self::assertEquals('static_sts', $credentials->getProviderName());
    }

    public function testRamRoleArn()
    {
        $vf = VirtualCLIConfig::full();
        $provider = new CLIProfileCredentialsProvider();

        $result = '{
    "Credentials": {
        "AccessKeySecret": "bar",
        "AccessKeyId": "foo",
        "Expiration": "2049-10-25T03:56:19Z",
        "SecurityToken": "token"
    }
}';
        Credentials::mockResponse(200, [], $result);

        $credentialsProvider = $this->invokeProtectedFunc($provider, 'reloadCredentialsProvider', $vf, 'RamRoleArn');
        $credentials = $credentialsProvider->getCredentials();
        self::assertEquals('foo', $credentials->getAccessKeyId());
        self::assertEquals('bar', $credentials->getAccessKeySecret());
        self::assertEquals('ram_role_arn/static_ak', $credentials->getProviderName());

        $credentials = $credentialsProvider->getCredentials();
        self::assertEquals('foo', $credentials->getAccessKeyId());
        self::assertEquals('bar', $credentials->getAccessKeySecret());
        self::assertEquals('ram_role_arn/static_ak', $credentials->getProviderName());
    }

    public function testChainableRamRoleArn()
    {
        $vf = VirtualCLIConfig::full();
        $provider = new CLIProfileCredentialsProvider();

        $result = '{
    "Credentials": {
        "AccessKeySecret": "bar",
        "AccessKeyId": "foo",
        "Expiration": "2049-10-25T03:56:19Z",
        "SecurityToken": "token"
    }
}';
        Credentials::mockResponse(200, [], $result);

        $credentialsProvider = $this->invokeProtectedFunc($provider, 'reloadCredentialsProvider', $vf, 'ChainableRamRoleArn');
        $credentials = $credentialsProvider->getCredentials();
        self::assertEquals('foo', $credentials->getAccessKeyId());
        self::assertEquals('bar', $credentials->getAccessKeySecret());
        self::assertEquals('ram_role_arn/static_ak', $credentials->getProviderName());

        $credentials = $credentialsProvider->getCredentials();
        self::assertEquals('foo', $credentials->getAccessKeyId());
        self::assertEquals('bar', $credentials->getAccessKeySecret());
        self::assertEquals('ram_role_arn/static_ak', $credentials->getProviderName());
    }

    public function testEcsRamRole()
    {
        $vf = VirtualCLIConfig::full();
        $provider = new CLIProfileCredentialsProvider();

        $result           = [
            'Expiration'      => '2049-10-01 00:00:00',
            'AccessKeyId'     => 'foo',
            'AccessKeySecret' => 'bar',
            'SecurityToken'   => 'token',
            'Code'            => 'Success',
        ];

        Credentials::mockResponse(200, [], 'Token');
        Credentials::mockResponse(200, [], $result);

        $credentialsProvider = $this->invokeProtectedFunc($provider, 'reloadCredentialsProvider', $vf, 'EcsRamRole');
        $credentials = $credentialsProvider->getCredentials();
        self::assertEquals('foo', $credentials->getAccessKeyId());
        self::assertEquals('bar', $credentials->getAccessKeySecret());
        self::assertEquals('ecs_ram_role', $credentials->getProviderName());

        $credentials = $credentialsProvider->getCredentials();
        self::assertEquals('foo', $credentials->getAccessKeyId());
        self::assertEquals('bar', $credentials->getAccessKeySecret());
        self::assertEquals('ecs_ram_role', $credentials->getProviderName());
    }

    public function testOIDCRoleArn()
    {
        $vf = VirtualCLIConfig::full();
        $provider = new CLIProfileCredentialsProvider();

        $this->expectException(InvalidArgumentException::class);
        if (method_exists($this, 'expectExceptionMessageMatches')) {
            $this->expectExceptionMessageMatches('/(?i)Failed to open stream/');
        } elseif (method_exists($this, 'expectExceptionMessageRegExp')) {
            $this->expectExceptionMessageRegExp('/(?i)Failed to open stream/');
        }

        $credentialsProvider = $this->invokeProtectedFunc($provider, 'reloadCredentialsProvider', $vf, 'OIDC');
        $credentialsProvider->getCredentials();
    }

    public function testCloudSSO()
    {
        $vf = VirtualCLIConfig::full();
        $provider = new CLIProfileCredentialsProvider();

        $result = '{"CloudCredential":{"AccessKeyId":"foo","AccessKeySecret":"bar","SecurityToken":"token","Expiration":"2049-10-25T03:56:19Z"}}';
        Credentials::mockResponse(200, [], $result);

        $credentialsProvider = $this->invokeProtectedFunc($provider, 'reloadCredentialsProvider', $vf, 'CloudSSO');
        $credentials = $credentialsProvider->getCredentials();
        self::assertEquals('foo', $credentials->getAccessKeyId());
        self::assertEquals('bar', $credentials->getAccessKeySecret());
        self::assertEquals('token', $credentials->getSecurityToken());
        self::assertEquals('cloud_sso', $credentials->getProviderName());

        $credentials = $credentialsProvider->getCredentials();
        self::assertEquals('foo', $credentials->getAccessKeyId());
        self::assertEquals('bar', $credentials->getAccessKeySecret());
        self::assertEquals('cloud_sso', $credentials->getProviderName());
    }

    public function testOAuth()
    {
        $vf = VirtualCLIConfig::full();
        $provider = new CLIProfileCredentialsProvider();

        $result = '{"accessKeyId":"foo","accessKeySecret":"bar","securityToken":"token","expiration":"2049-10-25T03:56:19Z"}';
        Credentials::mockResponse(200, [], $result);

        $credentialsProvider = $this->invokeProtectedFunc($provider, 'reloadCredentialsProvider', $vf, 'OAuth');
        $credentials = $credentialsProvider->getCredentials();
        self::assertEquals('foo', $credentials->getAccessKeyId());
        self::assertEquals('bar', $credentials->getAccessKeySecret());
        self::assertEquals('token', $credentials->getSecurityToken());
        self::assertEquals('oauth', $credentials->getProviderName());

        $credentials = $credentialsProvider->getCredentials();
        self::assertEquals('foo', $credentials->getAccessKeyId());
        self::assertEquals('bar', $credentials->getAccessKeySecret());
        self::assertEquals('oauth', $credentials->getProviderName());
    }

    public function testExternal()
    {
        $vf = VirtualCLIConfig::full();
        $provider = new CLIProfileCredentialsProvider();

        $credentialsProvider = $this->invokeProtectedFunc($provider, 'reloadCredentialsProvider', $vf, 'External');
        $credentials = $credentialsProvider->getCredentials();
        self::assertEquals('externalAk', $credentials->getAccessKeyId());
        self::assertEquals('externalSk', $credentials->getAccessKeySecret());
        self::assertEquals('external', $credentials->getProviderName());
    }

    public function testDisableCLI()
    {
        putenv("ALIBABA_CLOUD_CLI_PROFILE_DISABLED=true");

        $provider = new CLIProfileCredentialsProvider();

        $this->expectException(RuntimeException::class);
        if (method_exists($this, 'expectExceptionMessageMatches')) {
            $this->expectExceptionMessageMatches('/CLI credentials file is disabled/');
        } elseif (method_exists($this, 'expectExceptionMessageRegExp')) {
            $this->expectExceptionMessageRegExp('/CLI credentials file is disabled/');
        }
        $provider->getCredentials();

        putenv("ALIBABA_CLOUD_CLI_PROFILE_DISABLED=");
    }
}
