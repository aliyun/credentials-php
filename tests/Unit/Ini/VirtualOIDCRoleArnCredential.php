<?php

namespace AlibabaCloud\Credentials\Tests\Unit\Ini;

/**
 * Class VirtualOIDCRoleArnCredential
 *
 * @codeCoverageIgnore
 */
class VirtualOIDCRoleArnCredential extends VirtualAccessKeyCredential
{

    /**
     * @return string
     */
    public static function noRoleArn()
    {
        $content = <<<EOT
[phpunit]
enable = true
type = oidc_role_arn
role_session_name = role_session_name
oidc_provider_arn = oidc_provider_arn
oidc_token_file_path = oidc_token_file_path
policy = policy
EOT;

        return (new static($content))->url();
    }

    /**
     * @return string
     */
    public static function noRoleSessionName()
    {
        $content = <<<EOT
[phpunit]
enable = true
type = oidc_role_arn
role_arn = role_arn
oidc_provider_arn = oidc_provider_arn
oidc_token_file_path = oidc_token_file_path
policy = policy
EOT;

        return (new static($content))->url();
    }

    /**
     * @return string
     */
    public static function noTokenFile()
    {
        $content = <<<EOT
[phpunit]
enable = true
type = oidc_role_arn
role_arn = role_arn
role_session_name = role_session_name
oidc_provider_arn = oidc_provider_arn
policy = policy
EOT;

        return (new static($content))->url();
    }

    /**
     * @return string
     */
    public static function client($url)
    {
        $content = <<<EOT
[phpunit]
enable = true
type = oidc_role_arn
role_arn = role_arn
role_session_name = role_session_name
oidc_provider_arn = oidc_provider_arn
oidc_token_file_path = $url
policy = policy
EOT;

        return (new static($content))->url();
    }
}
