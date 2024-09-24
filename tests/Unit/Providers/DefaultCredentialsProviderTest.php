<?php

namespace AlibabaCloud\Credentials\Tests\Unit\Filter;

use AlibabaCloud\Credentials\Providers\DefaultCredentialsProvider;
use AlibabaCloud\Credentials\Providers\ProfileCredentialsProvider;
use AlibabaCloud\Credentials\Tests\Unit\Ini\VirtualAccessKeyCredential;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use InvalidArgumentException;
use SebastianBergmann\Environment\Console;

/**
 * Class DefaultCredentialsProviderTest
 *
 * @package AlibabaCloud\Credentials\Tests\Unit\Filter
 */
class DefaultCredentialsProviderTest extends TestCase
{
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

    public function testDefaultProvider()
    {
        $provider = new DefaultCredentialsProvider();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to load credentials from any of the providers in the chain: EnvironmentVariableCredentialsProvider: Access key ID must be specified via environment variable (ALIBABA_CLOUD_ACCESS_KEY_ID), CLIProfileCredentialsProvider: Credentials file is not readable: /Users/nanhe/.aliyun/config.json, ProfileCredentialsProvider: Credentials file is not readable: /Users/nanhe/.alibabacloud/credentials');
        $provider->getCredentials();
        // try {
        //     $provider->getCredentials();
        //     self::assertTrue(true);
        // } catch (RuntimeException $e) {
        //     self::assertEquals('Unable to load credentials from any of the providers in the chain: EnvironmentVariableCredentialsProvider: Access key ID must be specified via environment variable (ALIBABA_CLOUD_ACCESS_KEY_ID), CLIProfileCredentialsProvider: Credentials file is not readable: /Users/nanhe/.aliyun/config.json, ProfileCredentialsProvider: Credentials file is not readable: /Users/nanhe/.alibabacloud/credentials', $e->getMessage());
        // }
        
        // $vf = VirtualAccessKeyCredential::ok();
        // putenv("ALIBABA_CLOUD_CREDENTIALS_FILE=$vf");
        // $provider->getCredentials();
        // try {
        //     $provider->getCredentials();
        // } catch (RuntimeException $e) {
        //     self::assertEquals('Unable to load credentials from any of the providers in the chain: EnvironmentVariableCredentialsProvider: Environment variable ALIBABA_CLOUD_ACCESS_KEY_ID is not set, EnvironmentVariableCredentialsProvider: Environment variable ALIBABA_CLOUD_ACCESS_KEY_SECRET is not set, EnvironmentVariableCredentialsProvider: Environment variable ALIBABA_CLOUD_SECURITY_TOKEN is not set, CLIProfileCredentialsProvider: Profile file not found, ProfileCredentialsProvider: Profile', $e->getMessage());
            
        //     self::assertTrue(true);
        // }
        
    }
}
