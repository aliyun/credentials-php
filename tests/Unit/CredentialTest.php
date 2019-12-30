<?php

namespace AlibabaCloud\Credentials\Tests\Unit\Filter;

use AlibabaCloud\Credentials\Credential;
use AlibabaCloud\Credentials\RamRoleArnCredential;
use AlibabaCloud\Credentials\Signature\ShaHmac1Signature;
use Exception;
use PHPUnit\Framework\TestCase;
use ReflectionException;

/**
 * Class CredentialTest
 *
 * @package AlibabaCloud\Credentials\Tests\Unit\Filter
 */
class CredentialTest extends TestCase
{
    public function testLoad()
    {
        try {
            new Credential();
        } catch (Exception $exception) {
            self::assertEquals($exception->getMessage(), "Credential 'default' not found");
        }
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
            self::assertEquals($message, $e->getMessage());
        }
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
                'Missing required type option',
            ],

            [
                [
                    'type' => 'none',
                ],
                'Invalid type option, support: access_key, sts, ecs_ram_role, ram_role_arn, rsa_key_pair',
            ],

            [
                [
                    'type' => 'access_key',
                ],
                'Missing required access_key_id option in config for access_key',
            ],

            [
                [
                    'type'          => 'access_key',
                    'access_key_id' => 'foo',
                ],
                'Missing required access_key_secret option in config for access_key',
            ],

            [
                [
                    'type'              => 'access_key',
                    'access_key_id'     => '',
                    'access_key_secret' => 'bar',
                ],
                'access_key_id cannot be empty',
            ],

            [
                [
                    'type'              => 'access_key',
                    'access_key_id'     => 'foo',
                    'access_key_secret' => '',
                ],
                'access_key_secret cannot be empty',
            ],

            [
                [
                    'type' => 'sts',
                ],
                'Missing required access_key_id option in config for sts',
            ],

            [
                [
                    'type'          => 'sts',
                    'access_key_id' => 'foo',
                ],
                'Missing required access_key_secret option in config for sts',
            ],

            [
                [
                    'type'              => 'sts',
                    'access_key_id'     => 'foo',
                    'access_key_secret' => 'bar',
                ],
                'Missing required expiration option in config for sts',
            ],

            [
                [
                    'type'              => 'sts',
                    'access_key_id'     => '',
                    'access_key_secret' => 'bar',
                    'expiration'        => 3600,
                ],
                'access_key_id cannot be empty',
            ],

            [
                [
                    'type'              => 'sts',
                    'access_key_id'     => 'foo',
                    'access_key_secret' => '',
                    'expiration'        => 3600,
                ],
                'access_key_secret cannot be empty',
            ],

            [
                [
                    'type'              => 'sts',
                    'access_key_id'     => 'foo',
                    'access_key_secret' => 'bar',
                    'expiration'        => 'string',
                ],
                'expiration must be a int',
            ],

            [
                [
                    'type' => 'ecs_ram_role',
                ],
                'Missing required role_name option in config for ecs_ram_role',
            ],

            [
                [
                    'type'      => 'ecs_ram_role',
                    'role_name' => '',
                ],
                'role_name cannot be empty',
            ],

            [
                [
                    'type'      => 'ecs_ram_role',
                    'role_name' => 123456,
                ],
                'role_name must be a string',
            ],

            [
                [
                    'type' => 'ram_role_arn',
                ],
                'Missing required access_key_id option in config for ram_role_arn',
            ],

            [
                [
                    'type'          => 'ram_role_arn',
                    'access_key_id' => 'foo',
                ],
                'Missing required access_key_secret option in config for ram_role_arn',
            ],

            [
                [
                    'type'              => 'ram_role_arn',
                    'access_key_id'     => 'foo',
                    'access_key_secret' => 'bar',
                ],
                'Missing required role_arn option in config for ram_role_arn',
            ],

            [
                [
                    'type'              => 'ram_role_arn',
                    'access_key_id'     => 'foo',
                    'access_key_secret' => 'bar',
                    'role_arn'          => 'role_arn',
                ],
                'Missing required role_session_name option in config for ram_role_arn',
            ],

            [
                [
                    'type' => 'rsa_key_pair',
                ],
                'Missing required public_key_id option in config for rsa_key_pair',
            ],

            [
                [
                    'type'          => 'rsa_key_pair',
                    'public_key_id' => 'public_key_id',
                ],
                'Missing required private_key_file option in config for rsa_key_pair',
            ],

            [
                [
                    'type'             => 'rsa_key_pair',
                    'public_key_id'    => 'public_key_id',
                    'private_key_file' => '',
                ],
                'private_key_file cannot be empty',
            ],

            [
                [
                    'type'             => 'rsa_key_pair',
                    'public_key_id'    => 'public_key_id',
                    'private_key_file' => 'invalid_path',
                ],
                'file_get_contents(invalid_path): failed to open stream: No such file or directory',
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

        self::assertEquals('foo', $credential->getOriginalAccessKeyId());
        self::assertEquals('bar', $credential->getOriginalAccessKeySecret());
        self::assertEquals($config, $credential->getConfig());
        self::assertInstanceOf(RamRoleArnCredential::class, $credential->getCredential());
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
        self::assertEquals($config, $credential->getConfig());
        self::assertInstanceOf(ShaHmac1Signature::class, $credential->getSignature());
    }
}
