<?php

namespace AlibabaCloud\Credentials\Tests\Unit;

use AlibabaCloud\Credentials\Credential;
use AlibabaCloud\Credentials\Credentials;
use AlibabaCloud\Credentials\Credential\Config;
use Exception;
use PHPUnit\Framework\TestCase;
use ReflectionException;

/**
 * Class CredentialTest
 *
 * @package AlibabaCloud\Credentials\Tests\Unit
 */
class CredentialTest extends TestCase
{
    public function testLoad()
    {
        new Credential();
        self::assertTrue(true);
    }

    /**
     * @dataProvider exceptionCases
     *
     * @param array  $config
     * @param string $message
     */
    public function testException(array $config, $message)
    {
        try {
            new Credential($config);
        } catch (Exception $e) {
            self::assertEquals(strtolower($message), strtolower($e->getMessage()));
        }
        self::assertTrue(true);
    }

    /**
     * @return array
     */
    public function exceptionCases()
    {
        return [

            [
                [
                    'access_key_id' => 'access_key_id',
                ],
                'Unsupported credential type option: default, support: access_key, sts, bearer, ecs_ram_role, ram_role_arn, rsa_key_pair, oidc_role_arn, credentials_uri',
            ],

            [
                [
                    'type' => 'none',
                ],
                'Unsupported credential type option: none, support: access_key, sts, bearer, ecs_ram_role, ram_role_arn, rsa_key_pair, oidc_role_arn, credentials_uri',
            ],

            [
                [
                    'type' => 'access_key',
                ],
                'accessKeyId must be a string',
            ],

            [
                [
                    'type'          => 'access_key',
                    'access_key_id' => 'foo',
                ],
                'accessKeySecret must be a string',
            ],

            [
                [
                    'type'              => 'access_key',
                    'access_key_id'     => '',
                    'access_key_secret' => 'bar',
                ],
                'accessKeyId cannot be empty',
            ],

            [
                [
                    'type'              => 'access_key',
                    'access_key_id'     => 'foo',
                    'access_key_secret' => '',
                ],
                'accessKeySecret cannot be empty',
            ],

            [
                [
                    'type' => 'sts',
                ],
                'accessKeyId must be a string',
            ],

            [
                [
                    'type'          => 'sts',
                    'access_key_id' => 'foo',
                ],
                'accessKeySecret must be a string',
            ],

            [
                [
                    'type'              => 'sts',
                    'access_key_id'     => 'foo',
                    'access_key_secret' => 'bar',
                ],
                'securityToken must be a string',
            ],

            [
                [
                    'type'              => 'sts',
                    'access_key_id'     => '',
                    'access_key_secret' => 'bar',
                    'expiration'        => 3600,
                ],
                'accessKeyId cannot be empty',
            ],

            [
                [
                    'type'              => 'sts',
                    'access_key_id'     => 'foo',
                    'access_key_secret' => '',
                    'expiration'        => 3600,
                ],
                'accessKeySecret cannot be empty',
            ],

            [
                [
                    'type'              => 'sts',
                    'access_key_id'     => 'foo',
                    'access_key_secret' => 'bar',
                    'expiration'        => 'string',
                ],
                'securityToken must be a string',
            ],

            [
                [
                    'type'      => 'ecs_ram_role',
                    'role_name' => 123456,
                ],
                'roleName must be a string',
            ],

            [
                [
                    'type'      => 'ecs_ram_role',
                    'role_name' => 'test',
                    'disableIMDSv1' => 'false',
                ],
                'disableIMDSv1 must be a boolean',
            ],

            [
                [
                    'type' => 'ram_role_arn',
                ],
                'accessKeyId must be a string',
            ],

            [
                [
                    'type'          => 'ram_role_arn',
                    'access_key_id' => 'foo',
                ],
                'accessKeySecret must be a string',
            ],

            [
                [
                    'type'              => 'ram_role_arn',
                    'access_key_id'     => 'foo',
                    'access_key_secret' => 'bar',
                ],
                'roleArn cannot be empty',
            ],

            [
                [
                    'type'              => 'ram_role_arn',
                    'access_key_id'     => 'foo',
                    'access_key_secret' => 'bar',
                    'security_token'    => 'token',
                ],
                'roleArn cannot be empty',
            ],

            [
                [
                    'type' => 'rsa_key_pair',
                ],
                'publicKeyId must be a string',
            ],

            [
                [
                    'type'          => 'rsa_key_pair',
                    'public_key_id' => 'public_key_id',
                ],
                'privateKeyFile must be a string',
            ],

            [
                [
                    'type'             => 'rsa_key_pair',
                    'public_key_id'    => 'public_key_id',
                    'private_key_file' => '',
                ],
                'privateKeyFile cannot be empty',
            ],

            [
                [
                    'type'             => 'rsa_key_pair',
                    'public_key_id'    => 'public_key_id',
                    'private_key_file' => 'invalid_path',
                ],
                'file_get_contents(invalid_path): failed to open stream: No such file or directory',
            ],

            [
                [
                    'type' => 'oidc_role_arn',
                ],
                'roleArn cannot be empty',
            ],

            [
                [
                    'type' => 'oidc_role_arn',
                    'role_arn' => 'role_arn',
                ],
                'oidcProviderArn cannot be empty',
            ],

            [
                [
                    'type' => 'oidc_role_arn',
                    'role_arn' => 'role_arn',
                    'oidc_provider_arn' => 'oidc_provider_arn',
                ],
                'oidcTokenFilePath cannot be empty',
            ],

            [
                [
                    'type' => 'credentials_uri',
                ],
                'credentialsURI must be a string',
            ],

            [
                [
                    'type' => 'credentials_uri',
                    'credentialsURI' => ''
                ],
                'credentialsURI must be a string',
            ],

        ];
    }

    /**
     * @dataProvider exceptionConfigCases
     *
     * @param array  $config
     * @param string $message
     */
    public function testConfigException(array $map, $message)
    {
        try {
            $config  = new Config($map);
            new Credential($config);
        } catch (Exception $e) {
            self::assertEquals(strtolower($message), strtolower($e->getMessage()));
        }
        self::assertTrue(true);
    }

    /**
     * @return array
     */
    public function exceptionConfigCases()
    {
        return [

            [
                [
                    'accessKeyId' => 'access_key_id',
                ],
                'Unsupported credential type option: default, support: access_key, sts, bearer, ecs_ram_role, ram_role_arn, rsa_key_pair, oidc_role_arn, credentials_uri',
            ],

            [
                [
                    'type' => 'none',
                ],
                'Unsupported credential type option: none, support: access_key, sts, bearer, ecs_ram_role, ram_role_arn, rsa_key_pair, oidc_role_arn, credentials_uri',
            ],

            [
                [
                    'type' => 'access_key',
                ],
                'accessKeyId must be a string',
            ],

            [
                [
                    'type'          => 'access_key',
                    'accessKeyId' => 'foo',
                ],
                'accessKeySecret must be a string',
            ],

            [
                [
                    'type'              => 'access_key',
                    'accessKeyId'     => '',
                    'accessKeySecret' => 'bar',
                ],
                'accessKeyId cannot be empty',
            ],

            [
                [
                    'type'              => 'access_key',
                    'accessKeyId'     => 'foo',
                    'accessKeySecret' => '',
                ],
                'accessKeySecret cannot be empty',
            ],

            [
                [
                    'type' => 'sts',
                ],
                'accessKeyId must be a string',
            ],

            [
                [
                    'type'          => 'sts',
                    'accessKeyId' => 'foo',
                ],
                'accessKeySecret must be a string',
            ],

            [
                [
                    'type'              => 'sts',
                    'accessKeyId'     => 'foo',
                    'accessKeySecret' => 'bar',
                ],
                'securityToken must be a string',
            ],

            [
                [
                    'type'              => 'sts',
                    'accessKeyId'     => '',
                    'accessKeySecret' => 'bar',
                    'expiration'        => 3600,
                ],
                'accessKeyId cannot be empty',
            ],

            [
                [
                    'type'              => 'sts',
                    'accessKeyId'     => 'foo',
                    'accessKeySecret' => '',
                    'expiration'        => 3600,
                ],
                'accessKeySecret cannot be empty',
            ],

            [
                [
                    'type'              => 'sts',
                    'accessKeyId'     => 'foo',
                    'accessKeySecret' => 'bar',
                    'expiration'        => 'string',
                ],
                'securityToken must be a string',
            ],

            [
                [
                    'type'      => 'ecs_ram_role',
                    'roleName' => 123456,
                ],
                'roleName must be a string',
            ],

            [
                [
                    'type'      => 'ecs_ram_role',
                    'roleName' => 'test',
                    'disableIMDSv1' => 'false',
                ],
                'disableIMDSv1 must be a boolean',
            ],

            [
                [
                    'type' => 'ram_role_arn',
                ],
                'accessKeyId must be a string',
            ],

            [
                [
                    'type'          => 'ram_role_arn',
                    'accessKeyId' => 'foo',
                ],
                'accessKeySecret must be a string',
            ],

            [
                [
                    'type'              => 'ram_role_arn',
                    'accessKeyId'     => 'foo',
                    'accessKeySecret' => 'bar',
                ],
                'roleArn cannot be empty',
            ],

            [
                [
                    'type'              => 'ram_role_arn',
                    'accessKeyId'     => 'foo',
                    'accessKeySecret' => 'bar',
                    'securityToken'    => 'token',
                ],
                'roleArn cannot be empty',
            ],

            [
                [
                    'type' => 'rsa_key_pair',
                ],
                'publicKeyId must be a string',
            ],

            [
                [
                    'type'          => 'rsa_key_pair',
                    'publicKeyId' => 'public_key_id',
                ],
                'privateKeyFile must be a string',
            ],

            [
                [
                    'type'             => 'rsa_key_pair',
                    'publicKeyId'    => 'public_key_id',
                    'privateKeyFile' => '',
                ],
                'privateKeyFile cannot be empty',
            ],

            [
                [
                    'type'             => 'rsa_key_pair',
                    'publicKeyId'    => 'public_key_id',
                    'privateKeyFile' => 'invalid_path',
                ],
                'file_get_contents(invalid_path): failed to open stream: No such file or directory',
            ],

            [
                [
                    'type' => 'oidc_role_arn',
                ],
                'roleArn cannot be empty',
            ],

            [
                [
                    'type' => 'oidc_role_arn',
                    'roleArn' => 'role_arn',
                ],
                'oidcProviderArn cannot be empty',
            ],

            [
                [
                    'type' => 'oidc_role_arn',
                    'roleArn' => 'role_arn',
                    'oidcProviderArn' => 'oidc_provider_arn',
                ],
                'oidcTokenFilePath cannot be empty',
            ],

            [
                [
                    'type' => 'credentials_uri',
                ],
                'credentialsURI must be a string',
            ],

            [
                [
                    'type' => 'credentials_uri',
                    'credentialsURI' => ''
                ],
                'credentialsURI cannot be empty',
            ],

        ];
    }

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    public function testRamRoleArnCredential()
    {
        $config     = [
            'type'              => 'ram_role_arn',
            'access_key_id'     => 'foo',
            'access_key_secret' => 'bar',
            'role_arn'          => 'role_arn',
            'role_session_name' => 'role_session_name',
            'timeout'           => 1,
        ];
        $credential = new Credential($config);

        $result = '{
    "RequestId": "88FEA385-EF5D-4A8A-8C00-A07DAE3BFD44",
    "AssumedRoleUser": {
        "AssumedRoleId": "********************",
        "Arn": "********************"
    },
    "Credentials": {
        "AccessKeySecret": "********************",
        "AccessKeyId": "STS.**************",
        "Expiration": "2049-10-25T03:56:19Z",
        "SecurityToken": "**************"
    }
}';
        Credentials::mockResponse(200, [], $result);

        self::assertEquals('foo', $credential->getOriginalAccessKeyId());
        self::assertEquals('bar', $credential->getOriginalAccessKeySecret());
        self::assertEquals([
            'type'              => 'ram_role_arn',
            'accessKeyId'     => 'foo',
            'accessKeySecret' => 'bar',
            'roleArn'          => 'role_arn',
            'roleSessionName' => 'role_session_name',
        ], $credential->getConfig());
        self::assertEquals("STS.**************", $credential->getAccessKeyId());
        self::assertEquals("********************", $credential->getAccessKeySecret());
        self::assertEquals("**************", $credential->getSecurityToken());
        self::assertEquals("", $credential->getBearerToken());
        self::assertEquals("ram_role_arn", $credential->getType());
        $credentialModel = $credential->getCredential();
        self::assertEquals("STS.**************", $credentialModel->getAccessKeyId());
    }

    /**
     * @throws ReflectionException
     * @throws Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testAccessKey()
    {
        $config     = [
            'type'              => 'access_key',
            'access_key_id'     => 'foo',
            'access_key_secret' => 'bar',
        ];
        $credential = new Credential($config);

        self::assertEquals('foo', $credential->getAccessKeyId());
        self::assertEquals('bar', $credential->getAccessKeySecret());
        self::assertEquals("", $credential->getSecurityToken());
        self::assertEquals("", $credential->getBearerToken());
        self::assertEquals("access_key", $credential->getType());
        $config = $credential->getConfig();
        self::assertEquals('foo', $config['accessKeyId']);
        self::assertEquals('bar', $config['accessKeySecret']);
        $result = $credential->getCredential();
        self::assertEquals('foo', $result->getAccessKeyId());
        self::assertEquals('bar', $result->getAccessKeySecret());
        self::assertEquals('access_key', $result->getType());
    }

    /**
     * @throws GuzzleException
     * @throws ReflectionException
     */
    public function testSTS()
    {
        $config     = new Credential\Config([
            'type'            => 'sts',
            'accessKeyId'     => 'foo',
            'accessKeySecret' => 'bar',
            'securityToken'   => 'token',
        ]);
        $credential = new Credential($config);

        // Assert
        $result = $credential->getCredential();
        $this->assertEquals('foo', $result->getAccessKeyId());
        $this->assertEquals('bar', $result->getAccessKeySecret());
        $this->assertEquals('token', $result->getSecurityToken());
        $this->assertEquals('sts', $result->getType());
    }

    /**
     * @throws GuzzleException
     * @throws ReflectionException
     */
    public function testBearerToken()
    {
        $config     = new Credential\Config([
            'type'            => 'bearer',
            'bearerToken'     => 'token',
        ]);
        $credential = new Credential($config);

        // Assert
        $result = $credential->getCredential();
        $this->assertEquals('token', $result->getBearerToken());
        $this->assertEquals('bearer', $result->getType());
    }
}
