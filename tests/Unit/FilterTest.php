<?php

namespace AlibabaCloud\Credentials\Tests\Unit\Filter;

use AlibabaCloud\Credentials\Filter;
use Exception;
use PHPUnit\Framework\TestCase;

/**
 * Class FilterTest
 *
 * @package AlibabaCloud\Credentials\Tests\Unit\Filter
 */
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
                ' ',
                'AccessKeySecret',
                'access_key_secret is invalid',
            ],
            [
                'AccessKey',
                1,
                'access_key_secret must be a string',
            ],
            [
                'AccessKey',
                'AccessKey Secret ',
                'access_key_secret format is invalid',
            ],
        ];
    }
}
