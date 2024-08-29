<?php

namespace AlibabaCloud\Credentials\Tests\Unit\Ini;

use org\bovigo\vfs\vfsStream;

/**
 * Class VirtualCLIConfig
 *
 * @codeCoverageIgnore
 */
class VirtualCLIConfig
{

    /**
     * @var string Virtual config.json Content
     */
    private $content;
    /**
     * @var string File Name
     */
    private $fileName;

    /**
     * VirtualCLIConfig constructor.
     *
     * @param string $content
     * @param string $fileName
     */
    public function __construct($content, $fileName = '')
    {
        $this->content  = $content;
        $this->fileName = $fileName;
    }

    /**
     * @return string Virtual config.json Filename
     */
    public function url()
    {
        $fileName = 'config.json';
        if ($this->fileName) {
            $fileName .= "-$this->fileName";
        }

        return vfsStream::newFile($fileName)
                        ->withContent($this->content)
                        ->at(vfsStream::setup('.aliyun'))
                        ->url();
    }

    /**
     * @return string
     */
    public static function emptyContent()
    {
        $content = <<<EOT
{}
EOT;

        return (new static($content))->url();
    }

    /**
     * @return string
     */
    public static function badFormat()
    {
        $content = <<<EOT
invalid config
EOT;

        return (new static($content))->url();
    }

    /**
     * @return string
     */
    public static function noMode()
    {
        $content = <<<EOT
{
    "current": "AK",
    "profiles": [
        {
            "name": "AK",
            "access_key_id": "access_key_id",
            "access_key_secret": "access_key_secret"
        },
        {
            "name": "AK",
            "mode": "AK",
            "access_key_id": "access_key_id",
            "access_key_secret": "access_key_secret"
        }
    ]
}
EOT;

        return (new static($content))->url();
    }

    /**
     * @return string
     */
    public static function noName()
    {
        $content = <<<EOT
{
    "current": "AK",
    "profiles": [
        {
            "mode": "AK",
            "access_key_id": "access_key_id",
            "access_key_secret": "access_key_secret"
        }
    ]
}
EOT;

        return (new static($content))->url();
    }

    /**
     * @return string
     */
    public static function full()
    {
        $content = <<<EOT
{
    "current": "AK",
    "profiles": [
        {
            "name": "AK",
            "mode": "AK",
            "access_key_id": "access_key_id",
            "access_key_secret": "access_key_secret"
        },
        {
            "name": "RamRoleArn",
            "mode": "RamRoleArn",
            "access_key_id": "access_key_id",
            "access_key_secret": "access_key_secret",
            "ram_role_arn": "ram_role_arn",
            "ram_session_name": "ram_session_name",
            "expired_seconds": 3600,
            "sts_region": "cn-hangzhou"
        },
        {
            "name": "EcsRamRole",
            "mode": "EcsRamRole",
            "ram_role_name": "ram_role_name"
        },
        {
            "name": "OIDC",
            "mode": "OIDC",
            "ram_role_arn": "ram_role_arn",
            "oidc_token_file": "path/to/oidc/file",
            "oidc_provider_arn": "oidc_provider_arn",
            "ram_session_name": "ram_session_name",
            "expired_seconds": 3600,
            "sts_region": "cn-hangzhou"
        },
        {
            "name": "ChainableRamRoleArn",
            "mode": "ChainableRamRoleArn",
            "source_profile": "AK",
            "ram_role_arn": "ram_role_arn",
            "ram_session_name": "ram_session_name",
            "expired_seconds": 3600,
            "sts_region": "cn-hangzhou"
        }
    ]
}
EOT;

        return (new static($content))->url();
    }
}
