<?php

namespace AlibabaCloud\Credentials\Providers;

use AlibabaCloud\Credentials\Utils\Helper;
use RuntimeException;

/**
 * @internal This class is intended for internal use within the package. 
 * Class ProfileCredentialsProvider
 *
 * @package AlibabaCloud\Credentials\Providers
 */
class ProfileCredentialsProvider implements CredentialsProvider
{

    /**
     * @var string
     */
    private $profileName;

    /**
     * @var string
     */
    private $profileFile;

    /**
     * @var CredentialsProvider
     */
    private $credentialsProvider;


    /**
     * ProfileCredentialsProvider constructor.
     *
     * @param array $params
     */
    public function __construct(array $params = [])
    {
        $this->filterProfileName($params);
        $this->filterProfileFile();
    }

    private function filterProfileName(array $params)
    {
        if (Helper::envNotEmpty('ALIBABA_CLOUD_PROFILE')) {
            $this->profileName = Helper::env('ALIBABA_CLOUD_PROFILE');
        }

        if (isset($params['profileName'])) {
            $this->profileName = $params['profileName'];
        }

        if (is_null($this->profileName) || $this->profileName === '') {
            $this->profileName = 'default';
        }
    }

    private function filterProfileFile()
    {
        $this->profileFile = Helper::envNotEmpty('ALIBABA_CLOUD_CREDENTIALS_FILE');

        if (!$this->profileFile) {
            $this->profileFile = self::getDefaultFile();
        }
    }

    /**
     * @return bool
     */
    private function shouldReloadCredentialsProvider()
    {
        if (is_null($this->credentialsProvider)) {
            return true;
        }

        return false;
    }

    /**
     * @return CredentialsProvider
     */
    private function reloadCredentialsProvider($profileFile, $profileName)
    {
        if (!Helper::inOpenBasedir($profileFile)) {
            throw new RuntimeException('Unable to open credentials file: ' . $profileFile);
        }

        if (!\is_readable($profileFile) || !\is_file($profileFile)) {
            throw new RuntimeException('Credentials file is not readable: ' . $profileFile);
        }

        $fileArray = \parse_ini_file($profileFile, true);

        if (\is_array($fileArray) && !empty($fileArray)) {
            $credentialsConfigures = [];
            foreach (\array_change_key_case($fileArray) as $name => $configures) {
                if ($name === $profileName) {
                    $credentialsConfigures = $configures;
                    break;
                }
            }
            if (\is_array($credentialsConfigures) && !empty($credentialsConfigures)) {
                switch ($credentialsConfigures['type']) {
                    case 'access_key':
                        return new StaticAKCredentialsProvider([
                            'accessKeyId' => $credentialsConfigures['access_key_id'],
                            'accessKeySecret' => $credentialsConfigures['access_key_secret'],
                        ]);
                    case 'ram_role_arn':
                        $innerProvider = new StaticAKCredentialsProvider([
                            'accessKeyId' => $credentialsConfigures['access_key_id'],
                            'accessKeySecret' => $credentialsConfigures['access_key_secret'],
                        ]);
                        return new RamRoleArnCredentialsProvider([
                            'credentialsProvider' => $innerProvider,
                            'roleArn' => $credentialsConfigures['role_arn'],
                            'roleSessionName' => $credentialsConfigures['role_session_name'],
                            'policy' => $credentialsConfigures['policy'],
                        ]);
                    case 'ecs_ram_role':
                        return new EcsRamRoleCredentialsProvider([
                            'roleName' => $credentialsConfigures['role_name'],
                        ]);
                    case 'oidc_role_arn':
                        return new OIDCRoleArnCredentialsProvider([
                            'roleArn' => $credentialsConfigures['role_arn'],
                            'oidcProviderArn' => $credentialsConfigures['oidc_provider_arn'],
                            'oidcTokenFilePath' => $credentialsConfigures['oidc_token_file_path'],
                            'roleSessionName' => $credentialsConfigures['role_session_name'],
                            'policy' => $credentialsConfigures['policy'],
                        ]);
                    default:
                        throw new RuntimeException('Unsupported credential type form credentials file: ' . $credentialsConfigures['type']);
                }
            }
        }
        throw new RuntimeException('Failed to get credential form credentials file: ' . $profileFile);
    }
    /**
     * Get credential.
     *
     * @return Credentials
     * @throws RuntimeException
     */
    public function getCredentials()
    {
        if ($this->shouldReloadCredentialsProvider()) {
            $this->credentialsProvider = $this->reloadCredentialsProvider($this->profileFile, $this->profileName);
        }

        $credentials = $this->credentialsProvider->getCredentials();
        return new Credentials([
            'accessKeyId' => $credentials->getAccessKeyId(),
            'accessKeySecret' => $credentials->getAccessKeySecret(),
            'securityToken' => $credentials->getSecurityToken(),
            'providerName' => $this->getProviderName() . '/' . $this->credentialsProvider->getProviderName(),
        ]);

    }

    /**
     * Get the default credential file.
     *
     * @return string
     */
    private function getDefaultFile()
    {
        return Helper::getHomeDirectory() .
            DIRECTORY_SEPARATOR .
            '.alibabacloud' .
            DIRECTORY_SEPARATOR .
            'credentials';
    }

    /**
     * @return string
     */
    public function getProviderName()
    {
        return 'profile';
    }
}
