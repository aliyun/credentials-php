<?php

namespace AlibabaCloud\Credentials\Tests\Unit\Filter;

use AlibabaCloud\Credentials\Utils\Filter;
use InvalidArgumentException;
use Exception;
use PHPUnit\Framework\TestCase;

class FilterTest extends TestCase
{
    /**
     * @dataProvider accessKey
     *
     * @param string $accessKeyId
     * @param string $accessKeySecret
     * @param string $exceptionMessage
     */
    public function testAccessKey($accessKeyId, $accessKeySecret, $exceptionMessage)
    {
        try {
            Filter::accessKey($accessKeyId, $accessKeySecret);
        } catch (Exception $exception) {
            self::assertEquals($exceptionMessage, $exception->getMessage());
        }
    }

    /**
     * @return array
     */
    public function accessKey()
    {
        return [
            [
                '',
                'AccessKeySecret',
                'accessKeyId cannot be empty',
            ],
            [
                'AccessKey',
                '',
                'accessKeySecret cannot be empty',
            ],
            [
                1,
                'AccessKeySecret',
                'accessKeyId must be a string',
            ],
            [
                'AccessKey',
                1,
                'accessKeySecret must be a string',
            ]
        ];
    }

    /**
     * @dataProvider timeout
     *
     * @param string $connectTimeout
     * @param string $readTimeout
     * @param string $exceptionMessage
     */
    public function testTimeout($connectTimeout, $readTimeout, $exceptionMessage)
    {
        try {
            Filter::timeout($connectTimeout, $readTimeout);
        } catch (Exception $exception) {
            self::assertEquals($exceptionMessage, $exception->getMessage());
        }
    }

    /**
     * @return array
     */
    public function timeout()
    {
        return [
            [
                '',
                1,
                'connectTimeout must be a int',
            ],
            [
                1,
                '',
                'readTimeout must be a int',
            ]
        ];
    }

    public function testCredentialName()
    {
        try {
            Filter::credentialName(null);
        } catch (InvalidArgumentException $exception) {
            self::assertEquals('Name must be a string', $exception->getMessage());
        }
        try {
            Filter::credentialName(1);
        } catch (InvalidArgumentException $exception) {
            self::assertEquals('Name must be a string', $exception->getMessage());
        }
        try {
            Filter::credentialName('');
        } catch (InvalidArgumentException $exception) {
            self::assertEquals('Name cannot be empty', $exception->getMessage());
        }
        Filter::credentialName('1');
    }

    public function testBearerToken()
    {
        try {
            Filter::bearerToken(null);
        } catch (InvalidArgumentException $exception) {
            self::assertEquals('bearerToken must be a string', $exception->getMessage());
        }
        try {
            Filter::bearerToken(1);
        } catch (InvalidArgumentException $exception) {
            self::assertEquals('bearerToken must be a string', $exception->getMessage());
        }
        try {
            Filter::bearerToken('');
        } catch (InvalidArgumentException $exception) {
            self::assertEquals('bearerToken cannot be empty', $exception->getMessage());
        }
        Filter::bearerToken('1');
    }

    public function testPublicKeyId()
    {
        try {
            Filter::publicKeyId(null);
        } catch (InvalidArgumentException $exception) {
            self::assertEquals('publicKeyId must be a string', $exception->getMessage());
        }
        try {
            Filter::publicKeyId(1);
        } catch (InvalidArgumentException $exception) {
            self::assertEquals('publicKeyId must be a string', $exception->getMessage());
        }
        try {
            Filter::publicKeyId('');
        } catch (InvalidArgumentException $exception) {
            self::assertEquals('publicKeyId cannot be empty', $exception->getMessage());
        }
        Filter::publicKeyId('1');
    }

    public function testPrivateKeyFile()
    {
        try {
            Filter::privateKeyFile(null);
        } catch (InvalidArgumentException $exception) {
            self::assertEquals('privateKeyFile must be a string', $exception->getMessage());
        }
        try {
            Filter::privateKeyFile(1);
        } catch (InvalidArgumentException $exception) {
            self::assertEquals('privateKeyFile must be a string', $exception->getMessage());
        }
        try {
            Filter::privateKeyFile('');
        } catch (InvalidArgumentException $exception) {
            self::assertEquals('privateKeyFile cannot be empty', $exception->getMessage());
        }
        Filter::privateKeyFile('1');
    }

    public function testRoleName()
    {
        Filter::roleName(null);
        try {
            Filter::roleName(1);
        } catch (InvalidArgumentException $exception) {
            self::assertEquals('roleName must be a string', $exception->getMessage());
        }
        try {
            Filter::roleName('');
        } catch (InvalidArgumentException $exception) {
            self::assertEquals('roleName cannot be empty', $exception->getMessage());
        }
        Filter::roleName('1');
    }

    public function testDisableIMDSv1()
    {
        try {
            Filter::disableIMDSv1(null);
        } catch (InvalidArgumentException $exception) {
            self::assertEquals('disableIMDSv1 must be a boolean', $exception->getMessage());
        }
        try {
            Filter::disableIMDSv1(1);
        } catch (InvalidArgumentException $exception) {
            self::assertEquals('disableIMDSv1 must be a boolean', $exception->getMessage());
        }
        try {
            Filter::disableIMDSv1('');
        } catch (InvalidArgumentException $exception) {
            self::assertEquals('disableIMDSv1 must be a boolean', $exception->getMessage());
        }
        Filter::disableIMDSv1(true);
    }

    public function testRoleArn()
    {
        try {
            Filter::roleArn(null);
        } catch (InvalidArgumentException $exception) {
            self::assertEquals('roleArn cannot be empty', $exception->getMessage());
        }
        try {
            Filter::roleArn('');
        } catch (InvalidArgumentException $exception) {
            self::assertEquals('roleArn cannot be empty', $exception->getMessage());
        }
        Filter::roleArn('1');
    }

    public function testOidcProviderArn()
    {
        try {
            Filter::oidcProviderArn(null);
        } catch (InvalidArgumentException $exception) {
            self::assertEquals('oidcProviderArn cannot be empty', $exception->getMessage());
        }
        try {
            Filter::oidcProviderArn('');
        } catch (InvalidArgumentException $exception) {
            self::assertEquals('oidcProviderArn cannot be empty', $exception->getMessage());
        }
        Filter::roleName('1');
    }

    public function testOidcTokenFilePath()
    {
        try {
            Filter::oidcTokenFilePath(null);
        } catch (InvalidArgumentException $exception) {
            self::assertEquals('oidcTokenFilePath cannot be empty', $exception->getMessage());
        }
        try {
            Filter::oidcTokenFilePath('');
        } catch (InvalidArgumentException $exception) {
            self::assertEquals('oidcTokenFilePath cannot be empty', $exception->getMessage());
        }
        Filter::oidcTokenFilePath('1');
    }

    public function testSecurityToken()
    {
        try {
            Filter::securityToken(null);
        } catch (InvalidArgumentException $exception) {
            self::assertEquals('securityToken must be a string', $exception->getMessage());
        }
        try {
            Filter::securityToken(1);
        } catch (InvalidArgumentException $exception) {
            self::assertEquals('securityToken must be a string', $exception->getMessage());
        }
        try {
            Filter::securityToken('');
        } catch (InvalidArgumentException $exception) {
            self::assertEquals('securityToken cannot be empty', $exception->getMessage());
        }
        Filter::securityToken('1');
    }

    public function testExpiration()
    {
        try {
            Filter::expiration(true);
        } catch (InvalidArgumentException $exception) {
            self::assertEquals('expiration must be a int', $exception->getMessage());
        }
        try {
            Filter::expiration('');
        } catch (InvalidArgumentException $exception) {
            self::assertEquals('expiration must be a int', $exception->getMessage());
        }
        Filter::expiration(1);
    }

    public function testCredentialsURI()
    {
        try {
            Filter::credentialsURI(null);
        } catch (InvalidArgumentException $exception) {
            self::assertEquals('credentialsURI must be a string', $exception->getMessage());
        }
        try {
            Filter::credentialsURI(1);
        } catch (InvalidArgumentException $exception) {
            self::assertEquals('credentialsURI must be a string', $exception->getMessage());
        }
        try {
            Filter::credentialsURI('');
        } catch (InvalidArgumentException $exception) {
            self::assertEquals('credentialsURI cannot be empty', $exception->getMessage());
        }
        Filter::credentialsURI('1');
    }

    public function testReuseLastProviderEnabled()
    {
        try {
            Filter::reuseLastProviderEnabled(null);
        } catch (InvalidArgumentException $exception) {
            self::assertEquals('reuseLastProviderEnabled must be a boolean', $exception->getMessage());
        }
        try {
            Filter::reuseLastProviderEnabled(1);
        } catch (InvalidArgumentException $exception) {
            self::assertEquals('reuseLastProviderEnabled must be a boolean', $exception->getMessage());
        }
        try {
            Filter::reuseLastProviderEnabled('');
        } catch (InvalidArgumentException $exception) {
            self::assertEquals('reuseLastProviderEnabled must be a boolean', $exception->getMessage());
        }
        Filter::reuseLastProviderEnabled(true);
    }
}
