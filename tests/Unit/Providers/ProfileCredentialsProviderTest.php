<?php

namespace AlibabaCloud\Credentials\Tests\Unit\Providers;

use AlibabaCloud\Credentials\Credentials;
use AlibabaCloud\Credentials\Providers\ProfileCredentialsProvider;
use AlibabaCloud\Credentials\Tests\Mock\VirtualFile;
use AlibabaCloud\Credentials\Tests\Unit\Ini\VirtualAccessKeyCredential;
use AlibabaCloud\Credentials\Tests\Unit\Ini\VirtualRamRoleArnCredential;
use AlibabaCloud\Credentials\Tests\Unit\Ini\VirtualEcsRamRoleCredential;
use AlibabaCloud\Credentials\Tests\Unit\Ini\VirtualRsaKeyPairCredential;
use AlibabaCloud\Credentials\Tests\Unit\Ini\VirtualOIDCRoleArnCredential;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;
use InvalidArgumentException;

/**
 * Class ProfileCredentialsProviderTest
 *
 * @package AlibabaCloud\Credentials\Tests\Unit\Providers
 */
class ProfileCredentialsProviderTest extends TestCase
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
        $reflection = new ReflectionClass(ProfileCredentialsProvider::class);
        $privateProperty = $reflection->getProperty($field);
        $privateProperty->setAccessible(true);
        return $privateProperty->getValue($instance);
    }

    public function testConstruct()
    {
        // Setup
        $provider = new ProfileCredentialsProvider();

        $profileName = $this->getPrivateField($provider, 'profileName');
        $profileFile = $this->getPrivateField($provider, 'profileFile');
        self::assertEquals('default', $profileName);
        self::assertTrue(strpos($profileFile, '.alibabacloud' . DIRECTORY_SEPARATOR . 'credentials') !== false);

        $params = [
            'profileName' => 'test',
        ];
        putenv("ALIBABA_CLOUD_PROFILE=profileName");
        putenv("ALIBABA_CLOUD_CREDENTIALS_FILE=/a/b");

        $provider = new ProfileCredentialsProvider($params);

        $profileName = $this->getPrivateField($provider, 'profileName');
        $profileFile = $this->getPrivateField($provider, 'profileFile');

        self::assertEquals('test', $profileName);
        self::assertEquals('/a/b', $profileFile);
        self::assertEquals('profile', $provider->getProviderName());

        putenv("ALIBABA_CLOUD_PROFILE=");
        putenv("ALIBABA_CLOUD_CREDENTIALS_FILE=");
    }

    public function testInvalidType()
    {
        $vf = VirtualAccessKeyCredential::invalidType();
        putenv("ALIBABA_CLOUD_CREDENTIALS_FILE=$vf");
        putenv("ALIBABA_CLOUD_PROFILE=phpunit");
        $this->expectException(RuntimeException::class);
        if (method_exists($this, 'expectExceptionMessageMatches')) {
            $this->expectExceptionMessageMatches('/Unsupported credential type from credentials file: invalidType/');
        } elseif (method_exists($this, 'expectExceptionMessageRegExp')) {
            $this->expectExceptionMessageRegExp('/Unsupported credential type from credentials file: invalidType/');
        }
        $provider = new ProfileCredentialsProvider();
        $provider->getCredentials();
        putenv("ALIBABA_CLOUD_PROFILE=");
        putenv("ALIBABA_CLOUD_CREDENTIALS_FILE=");
    }

    public function testAK()
    {
        $vf = VirtualAccessKeyCredential::ok();
        putenv("ALIBABA_CLOUD_CREDENTIALS_FILE=$vf");
        putenv("ALIBABA_CLOUD_PROFILE=ok");
        $provider = new ProfileCredentialsProvider();
        $credentials = $provider->getCredentials();
        self::assertEquals('foo', $credentials->getAccessKeyId());
        self::assertEquals('bar', $credentials->getAccessKeySecret());
        self::assertEquals('profile/static_ak', $credentials->getProviderName());
        putenv("ALIBABA_CLOUD_PROFILE=");
        putenv("ALIBABA_CLOUD_CREDENTIALS_FILE=");
    }

    public function testAKNoKeyError()
    {
        $vf = VirtualAccessKeyCredential::noKey();
        putenv("ALIBABA_CLOUD_CREDENTIALS_FILE=$vf");
        putenv("ALIBABA_CLOUD_PROFILE=phpunit");
        $this->expectException(InvalidArgumentException::class);
        if (method_exists($this, 'expectExceptionMessageMatches')) {
            $this->expectExceptionMessageMatches('/accessKeyId must be a string/');
        } elseif (method_exists($this, 'expectExceptionMessageRegExp')) {
            $this->expectExceptionMessageRegExp('/accessKeyId must be a string/');
        }
        $provider = new ProfileCredentialsProvider();
        $provider->getCredentials();
        putenv("ALIBABA_CLOUD_PROFILE=");
        putenv("ALIBABA_CLOUD_CREDENTIALS_FILE=");
    }

    public function testAKNoTypeError()
    {
        $vf = VirtualAccessKeyCredential::noType();
        putenv("ALIBABA_CLOUD_CREDENTIALS_FILE=$vf");
        putenv("ALIBABA_CLOUD_PROFILE=phpunit");
        $this->expectException(RuntimeException::class);
        if (method_exists($this, 'expectExceptionMessageMatches')) {
            $this->expectExceptionMessageMatches('/Unsupported credential type from credentials file/');
        } elseif (method_exists($this, 'expectExceptionMessageRegExp')) {
            $this->expectExceptionMessageRegExp('/Unsupported credential type from credentials file/');
        }
        $provider = new ProfileCredentialsProvider();
        $provider->getCredentials();
        putenv("ALIBABA_CLOUD_PROFILE=");
        putenv("ALIBABA_CLOUD_CREDENTIALS_FILE=");
    }

    public function testRamRoleArn()
    {
        $vf = VirtualRamRoleArnCredential::client();
        putenv("ALIBABA_CLOUD_CREDENTIALS_FILE=$vf");
        putenv("ALIBABA_CLOUD_PROFILE=phpunit");
        $provider = new ProfileCredentialsProvider();

        $result = '{
    "Credentials": {
        "AccessKeySecret": "bar",
        "AccessKeyId": "foo",
        "Expiration": "2049-10-25T03:56:19Z",
        "SecurityToken": "token"
    }
}';
        Credentials::mockResponse(200, [], $result);

        $credentials = $provider->getCredentials();
        self::assertEquals('foo', $credentials->getAccessKeyId());
        self::assertEquals('bar', $credentials->getAccessKeySecret());
        self::assertEquals('profile/ram_role_arn/static_ak', $credentials->getProviderName());
        putenv("ALIBABA_CLOUD_PROFILE=");
        putenv("ALIBABA_CLOUD_CREDENTIALS_FILE=");
    }

    public function testRamRoleArnError()
    {
        $vf = VirtualRamRoleArnCredential::noRoleArn();
        putenv("ALIBABA_CLOUD_CREDENTIALS_FILE=$vf");
        putenv("ALIBABA_CLOUD_PROFILE=phpunit");
        $provider = new ProfileCredentialsProvider();
        $this->expectException(InvalidArgumentException::class);
        if (method_exists($this, 'expectExceptionMessageMatches')) {
            $this->expectExceptionMessageMatches('/roleArn cannot be empty/');
        } elseif (method_exists($this, 'expectExceptionMessageRegExp')) {
            $this->expectExceptionMessageRegExp('/roleArn cannot be empty/');
        }
        $provider->getCredentials();
        putenv("ALIBABA_CLOUD_PROFILE=");
        putenv("ALIBABA_CLOUD_CREDENTIALS_FILE=");
    }

    public function testEcsRamRole()
    {
        $vf = VirtualEcsRamRoleCredential::client();
        putenv("ALIBABA_CLOUD_CREDENTIALS_FILE=$vf");
        putenv("ALIBABA_CLOUD_PROFILE=phpunit");
        $provider = new ProfileCredentialsProvider();

        $result           = [
            'Expiration'      => '2049-10-01 00:00:00',
            'AccessKeyId'     => 'foo',
            'AccessKeySecret' => 'bar',
            'SecurityToken'   => 'token',
            'Code'            => 'Success',
        ];

        Credentials::mockResponse(200, [], 'Token');
        Credentials::mockResponse(200, [], $result);

        $credentials = $provider->getCredentials();
        self::assertEquals('foo', $credentials->getAccessKeyId());
        self::assertEquals('bar', $credentials->getAccessKeySecret());
        self::assertEquals('profile/ecs_ram_role', $credentials->getProviderName());
        putenv("ALIBABA_CLOUD_PROFILE=");
        putenv("ALIBABA_CLOUD_CREDENTIALS_FILE=");
    }

    public function testOIDCRoleArn()
    {
        $vf = new VirtualFile("token");
        $url = $vf->url("token-file");
        $vf = VirtualOIDCRoleArnCredential::client($url);
        putenv("ALIBABA_CLOUD_CREDENTIALS_FILE=$vf");
        putenv("ALIBABA_CLOUD_PROFILE=phpunit");

        $this->expectException(InvalidArgumentException::class);
        if (method_exists($this, 'expectExceptionMessageMatches')) {
            $this->expectExceptionMessageMatches('/(?i)failed to open stream/');
        } elseif (method_exists($this, 'expectExceptionMessageRegExp')) {
            $this->expectExceptionMessageRegExp('/(?i)failed to open stream/');
        }
        $provider = new ProfileCredentialsProvider();
        $provider->getCredentials();
        putenv("ALIBABA_CLOUD_PROFILE=");
        putenv("ALIBABA_CLOUD_CREDENTIALS_FILE=");
    }

    public function testOIDCRoleArnError()
    {
        $vf = VirtualOIDCRoleArnCredential::noRoleArn();
        putenv("ALIBABA_CLOUD_CREDENTIALS_FILE=$vf");
        putenv("ALIBABA_CLOUD_PROFILE=phpunit");
        $provider = new ProfileCredentialsProvider();
        $this->expectException(InvalidArgumentException::class);
        if (method_exists($this, 'expectExceptionMessageMatches')) {
            $this->expectExceptionMessageMatches('/roleArn cannot be empty/');
        } elseif (method_exists($this, 'expectExceptionMessageRegExp')) {
            $this->expectExceptionMessageRegExp('/roleArn cannot be empty/');
        }
        $provider->getCredentials();
        putenv("ALIBABA_CLOUD_PROFILE=");
        putenv("ALIBABA_CLOUD_CREDENTIALS_FILE=");
    }

    public function testRsaKeyPairNoPrivateKeyFile()
    {
        $vf = VirtualRsaKeyPairCredential::noPrivateKeyFile();
        putenv("ALIBABA_CLOUD_CREDENTIALS_FILE=$vf");
        putenv("ALIBABA_CLOUD_PROFILE=phpunit");
        $provider = new ProfileCredentialsProvider();
        $this->expectException(InvalidArgumentException::class);
        if (method_exists($this, 'expectExceptionMessageMatches')) {
            $this->expectExceptionMessageMatches('/privateKeyFile must be a string/');
        } elseif (method_exists($this, 'expectExceptionMessageRegExp')) {
            $this->expectExceptionMessageRegExp('/privateKeyFile must be a string/');
        }
        $provider->getCredentials();
        putenv("ALIBABA_CLOUD_PROFILE=");
        putenv("ALIBABA_CLOUD_CREDENTIALS_FILE=");
    }

    public function testRsaKeyPairNoPublicKeyId()
    {
        $vf = VirtualRsaKeyPairCredential::noPublicKeyId();
        putenv("ALIBABA_CLOUD_CREDENTIALS_FILE=$vf");
        putenv("ALIBABA_CLOUD_PROFILE=phpunit");
        $provider = new ProfileCredentialsProvider();
        $this->expectException(InvalidArgumentException::class);
        if (method_exists($this, 'expectExceptionMessageMatches')) {
            $this->expectExceptionMessageMatches('/publicKeyId must be a string/');
        } elseif (method_exists($this, 'expectExceptionMessageRegExp')) {
            $this->expectExceptionMessageRegExp('/publicKeyId must be a string/');
        }
        $provider->getCredentials();
        putenv("ALIBABA_CLOUD_PROFILE=");
        putenv("ALIBABA_CLOUD_CREDENTIALS_FILE=");
    }

    public function testSetIniError()
    {
        $this->expectException(RuntimeException::class);
        if (method_exists($this, 'expectExceptionMessageMatches')) {
            $this->expectExceptionMessageMatches('/Unable to open credentials file/');
        } elseif (method_exists($this, 'expectExceptionMessageRegExp')) {
            $this->expectExceptionMessageRegExp('/Unable to open credentials file/');
        }
        putenv('ALIBABA_CLOUD_CREDENTIALS_FILE=/c/d');
        $provider = new ProfileCredentialsProvider();
        $provider->getCredentials();
        putenv('ALIBABA_CLOUD_CREDENTIALS_FILE=');
    }
}
