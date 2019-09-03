<?php

namespace AlibabaCloud\Credentials\Tests\Unit;

use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;
use AlibabaCloud\Credentials\Credentials;
use AlibabaCloud\Credentials\RamRoleArnCredential;

class MockTraitTest extends TestCase
{

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @expectedException \GuzzleHttp\Exception\RequestException
     * @expectedExceptionMessage Error
     */
    public function testRequestException()
    {
        Credentials::mockRequestException('Error', new Request('GET', 'test'));
        $credential = new RamRoleArnCredential([
                                                   'access_key_id'     => 'access_key_id',
                                                   'access_key_secret' => 'access_key_secret',
                                                   'role_arn'          => 'role_arn',
                                                   'role_session_name' => 'role_session_name',
                                                   'policy'            => [],
                                               ]);

        self::assertEquals('STS.**************', $credential->getAccessKeyId());
    }
}
