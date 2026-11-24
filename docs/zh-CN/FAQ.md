# Alibaba Cloud Credentials for PHP - 用户说明文档

## 1. 项目简介

### 1.1 项目用途

Alibaba Cloud Credentials for PHP 是一个专为 PHP 开发者设计的凭证管理工具库，用于简化和统一阿里云 API 调用时的身份认证过程。该库提供了多种凭证获取方式，支持自动凭证轮换和刷新，帮助开发者安全、便捷地管理访问凭证。

### 1.2 核心能力

- **多种凭证类型支持**：支持 AccessKey、STS、EcsRamRole、RamRoleArn、OIDCRoleArn、RSA密钥对、Bearer Token、凭证URI等多种认证方式
- **默认凭证提供链**：支持按优先级自动从环境变量、配置文件、实例元数据等多个来源获取凭证
- **自动凭证刷新**：对于临时凭证（如STS、RAM角色），支持自动检测过期并刷新
- **配置文件支持**：兼容阿里云CLI配置文件（`~/.aliyun/config.json`）和自定义凭证文件（`~/.alibabacloud/credentials`）
- **安全性增强**：支持 IMDSv2（Instance Metadata Service v2）加固模式，提升ECS实例元数据访问的安全性

### 1.3 典型应用场景

- **开发测试环境**：通过环境变量或配置文件快速配置不同的访问凭证
- **生产环境**：使用ECS实例RAM角色或容器服务RRSA（RAM Roles for Service Accounts）实现无密钥部署
- **跨账号访问**：通过 RamRoleArn 实现不同阿里云账号之间的安全访问
- **临时授权场景**：使用STS临时凭证实现短期、受限的资源访问
- **云原生应用**：在Kubernetes环境中通过OIDC令牌实现细粒度的权限控制

---

## 2. 使用前提与环境要求

### 2.1 操作系统要求

- **支持平台**：Linux、macOS、Windows
- **特殊说明**：
  - Windows系统下配置文件路径为 `C:\Users\USER_NAME\.alibabacloud\credentials` 和 `C:\Users\USER_NAME\.aliyun\config.json`
  - Linux/macOS下配置文件路径为 `~/.alibabacloud/credentials` 和 `~/.aliyun/config.json`

### 2.2 运行时环境

#### PHP 版本要求
- **最低要求**：PHP 5.6 或更高版本
- **推荐版本**：PHP 7.4+ 或 PHP 8.x（更好的性能和安全性）

#### 必需的 PHP 扩展
以下扩展必须启用，否则库无法正常工作：
- `ext-curl`：用于HTTP请求
- `ext-json`：用于JSON数据处理
- `ext-libxml`：用于XML处理
- `ext-openssl`：用于加密操作
- `ext-mbstring`：用于多字节字符串处理
- `ext-simplexml`：用于简单XML操作
- `ext-xmlwriter`：用于XML写入

**检查方法**：
```bash
php -m | grep -E "curl|json|openssl|mbstring|simplexml|xmlwriter|libxml"
```

如有缺失，需安装对应扩展（以Ubuntu为例）：
```bash
sudo apt-get install php-curl php-json php-xml php-mbstring
```

#### Composer 依赖管理器
- **必需**：项目使用 Composer 管理依赖
- **安装方法**：参见 [Composer官网](https://getcomposer.org/)

### 2.3 第三方依赖库

项目主要依赖以下库（通过Composer自动安装）：
- `guzzlehttp/guzzle` (^6.3 或 ^7.0)：HTTP客户端
- `adbario/php-dot-notation` (^2.2)：数组/对象点符号访问
- `alibabacloud/tea` (^3.0)：阿里云通用工具库

### 2.4 网络要求

根据使用的凭证类型，可能需要访问以下网络地址：

- **ECS实例RAM角色**：需访问元数据服务 `http://100.100.100.200`（仅在ECS/ECI实例内部可用）
- **RamRoleArn / OIDCRoleArn**：需访问STS服务端点（默认为公网地址，可通过 `stsEndpoint` 参数指定VPC内网地址）
- **凭证URI**：需访问自定义的凭证服务URL
- **RSA密钥对**：需访问STS服务进行令牌交换

**防火墙注意事项**：
- 如在受限网络环境下使用，需确保能访问阿里云STS服务（`sts.aliyuncs.com` 或区域化端点）
- ECS实例角色需确保安全组未封禁 `100.100.100.200:80`

### 2.5 权限要求

- **文件系统权限**：
  - 配置文件（`~/.alibabacloud/credentials`、`~/.aliyun/config.json`）需可读权限
  - OIDC令牌文件需可读权限
  - RSA私钥文件需可读权限
  
- **阿里云权限**：
  - AccessKey需具备调用目标API的权限
  - RAM角色需被正确授权并建立信任关系
  - ECS实例需绑定有效的实例RAM角色
  - OIDC IdP需在RAM中正确配置

---

## 3. 快速开始指南

### 3.1 安装

#### 使用 Composer 安装（推荐）

```bash
composer require alibabacloud/credentials
```

如遇网络问题，可切换至阿里云镜像：
```bash
composer config -g repo.packagist composer https://mirrors.aliyun.com/composer/
composer require alibabacloud/credentials
```

#### 验证安装

```bash
composer show alibabacloud/credentials
```

### 3.2 基础配置

#### 方式1：通过环境变量配置

```bash
# AccessKey方式
export ALIBABA_CLOUD_ACCESS_KEY_ID="your-access-key-id"
export ALIBABA_CLOUD_ACCESS_KEY_SECRET="your-access-key-secret"

# STS临时凭证方式（包含SecurityToken）
export ALIBABA_CLOUD_ACCESS_KEY_ID="your-access-key-id"
export ALIBABA_CLOUD_ACCESS_KEY_SECRET="your-access-key-secret"
export ALIBABA_CLOUD_SECURITY_TOKEN="your-security-token"
```

#### 方式2：通过配置文件

创建文件 `~/.alibabacloud/credentials`：

```ini
[default]
type = access_key
access_key_id = your-access-key-id
access_key_secret = your-access-key-secret

[production]
type = ecs_ram_role
role_name = MyEcsRole
```

指定使用特定配置：
```bash
export ALIBABA_CLOUD_PROFILE=production
```

### 3.3 基本使用示例

#### 示例1：使用默认凭证提供链

```php
<?php
use AlibabaCloud\Credentials\Credential;

// 自动从环境变量、配置文件、实例角色等来源获取凭证
$credential = new Credential();
$cred = $credential->getCredential();

echo "Access Key ID: " . $cred->getAccessKeyId() . "\n";
echo "Access Key Secret: " . $cred->getAccessKeySecret() . "\n";
```

#### 示例2：显式指定 AccessKey

```php
<?php
use AlibabaCloud\Credentials\Credential;
use AlibabaCloud\Credentials\Credential\Config;

$config = new Config([
    'type' => 'access_key',
    'accessKeyId' => 'your-access-key-id',
    'accessKeySecret' => 'your-access-key-secret',
]);

$credential = new Credential($config);
$cred = $credential->getCredential();
```

#### 示例3：使用 ECS 实例角色

```php
<?php
use AlibabaCloud\Credentials\Credential;
use AlibabaCloud\Credentials\Credential\Config;

$config = new Config([
    'type' => 'ecs_ram_role',
    'roleName' => 'MyEcsRole',  // 可选，不指定则自动获取
    'disableIMDSv1' => false,   // 是否禁用IMDSv1作为降级方案
]);

$credential = new Credential($config);
$cred = $credential->getCredential();
```

### 3.4 启动与验证

创建测试脚本 `test_credential.php`：

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use AlibabaCloud\Credentials\Credential;

try {
    $credential = new Credential();
    $cred = $credential->getCredential();
    
    echo "✓ 凭证获取成功！\n";
    echo "  Provider: " . $cred->getProviderName() . "\n";
    echo "  Access Key ID: " . substr($cred->getAccessKeyId(), 0, 10) . "...\n";
    echo "  Type: " . $cred->getType() . "\n";
} catch (\Exception $e) {
    echo "✗ 凭证获取失败：" . $e->getMessage() . "\n";
}
```

运行验证：
```bash
php test_credential.php
```

**预期输出示例**（AccessKey方式）：
```
✓ 凭证获取成功！
  Provider: default/env
  Access Key ID: LTAI4G3a9c...
  Type: access_key
```

---

## 4. 常见问题解答（FAQ）

### 4.1 环境变量相关问题

#### Q1: 提示 "Access key ID must be specified via environment variable (ALIBABA_CLOUD_ACCESS_KEY_ID)"

**问题现象**：
```
InvalidArgumentException: Access key ID must be specified via environment variable (ALIBABA_CLOUD_ACCESS_KEY_ID)
```

**根本原因**：  
使用默认凭证提供链时，程序按优先级尝试从环境变量获取凭证，但未设置 `ALIBABA_CLOUD_ACCESS_KEY_ID` 环境变量。查看代码 `src/Providers/EnvironmentVariableCredentialsProvider.php:29-33`，该提供者会检查环境变量是否非空。

**解决方案**：
1. **方案A**：设置环境变量
   ```bash
   export ALIBABA_CLOUD_ACCESS_KEY_ID="your-access-key-id"
   export ALIBABA_CLOUD_ACCESS_KEY_SECRET="your-access-key-secret"
   ```

2. **方案B**：使用配置文件代替环境变量（创建 `~/.alibabacloud/credentials` 文件）

3. **方案C**：显式指定凭证类型，避免使用默认链：
   ```php
   $config = new Config(['type' => 'access_key', ...]);
   $credential = new Credential($config);
   ```

---

#### Q2: 环境变量已设置但仍提示找不到

**问题现象**：  
设置了环境变量，但程序仍报错未找到凭证

**根本原因**：
- PHP CLI和Web服务器（如Apache/Nginx+PHP-FPM）使用不同的环境变量作用域
- 在 `.bashrc` 中设置的变量仅对当前shell会话有效
- PHP可能因 `open_basedir` 或其他安全限制无法读取环境变量

**解决方案**：
1. **PHP CLI**：确保在同一shell会话中设置变量并运行脚本
   ```bash
   export ALIBABA_CLOUD_ACCESS_KEY_ID="xxx"
   php script.php
   ```

2. **PHP-FPM**：在php-fpm配置文件中设置
   ```ini
   ; /etc/php-fpm.d/www.conf
   env[ALIBABA_CLOUD_ACCESS_KEY_ID] = "your-access-key-id"
   env[ALIBABA_CLOUD_ACCESS_KEY_SECRET] = "your-access-key-secret"
   ```
   然后重启php-fpm：`sudo systemctl restart php-fpm`

3. **Apache**：在虚拟主机配置或 `.htaccess` 中设置
   ```apache
   SetEnv ALIBABA_CLOUD_ACCESS_KEY_ID "your-access-key-id"
   SetEnv ALIBABA_CLOUD_ACCESS_KEY_SECRET "your-access-key-secret"
   ```

4. **验证环境变量**：
   ```php
   var_dump(getenv('ALIBABA_CLOUD_ACCESS_KEY_ID'));
   ```

---

#### Q3: SecurityToken 相关的错误

**问题现象**：
```
InvalidArgumentException: securityToken cannot be empty
```

**根本原因**：  
当同时设置了 `ALIBABA_CLOUD_ACCESS_KEY_ID`、`ALIBABA_CLOUD_ACCESS_KEY_SECRET` 和 `ALIBABA_CLOUD_SECURITY_TOKEN` 环境变量时，程序会将其识别为STS临时凭证。如果 `ALIBABA_CLOUD_SECURITY_TOKEN` 被设置为空字符串（而非未设置），Filter类的校验会失败（`src/Utils/Filter.php:174-183`）。

**解决方案**：
1. 如不使用STS，完全删除该环境变量（而非设置为空）：
   ```bash
   unset ALIBABA_CLOUD_SECURITY_TOKEN
   ```

2. 如使用STS，确保SecurityToken有效且未过期：
   ```bash
   export ALIBABA_CLOUD_SECURITY_TOKEN="your-valid-token"
   ```

---

### 4.2 配置文件相关问题

#### Q4: 提示 "Credentials file is not readable"

**问题现象**：
```
RuntimeException: Credentials file is not readable: /home/user/.alibabacloud/credentials
```

**根本原因**：  
代码 `src/Providers/ProfileCredentialsProvider.php:89-91` 会检查配置文件是否可读且为文件。可能原因：
- 文件不存在
- 文件权限不足（需要读权限）
- 文件被 `open_basedir` 限制在可访问目录之外

**解决方案**：
1. **检查文件是否存在**：
   ```bash
   ls -la ~/.alibabacloud/credentials
   ```

2. **确保文件可读**：
   ```bash
   chmod 600 ~/.alibabacloud/credentials
   ```

3. **检查 open_basedir 限制**：
   ```php
   echo ini_get('open_basedir');
   ```
   如果设置了 `open_basedir`，需确保配置文件路径在允许范围内，或在 php.ini 中调整：
   ```ini
   open_basedir = /var/www:/home/user/.alibabacloud
   ```

4. **手动指定配置文件路径**：
   ```bash
   export ALIBABA_CLOUD_CREDENTIALS_FILE="/custom/path/credentials"
   ```

---

#### Q5: 配置文件格式错误导致解析失败

**问题现象**：
```
RuntimeException: Failed to get credential from credentials file
```

**根本原因**：  
配置文件使用INI格式（`~/.alibabacloud/credentials`）或JSON格式（`~/.aliyun/config.json`），格式错误或缺少必需字段会导致解析失败。

**解决方案**：

**INI格式示例**（`~/.alibabacloud/credentials`）：
```ini
[default]
type = access_key
access_key_id = LTAI4xxxxx
access_key_secret = xxxxxx

[ecs]
type = ecs_ram_role
role_name = MyRole
```

**JSON格式示例**（`~/.aliyun/config.json`）：
```json
{
  "current": "default",
  "profiles": [
    {
      "name": "default",
      "mode": "AK",
      "access_key_id": "your-access-key-id",
      "access_key_secret": "your-access-key-secret"
    }
  ]
}
```

**常见格式错误**：
- INI文件缺少 `[section]` 标记
- JSON文件缺少 `current` 或 `profiles` 字段
- 字段名拼写错误（如 `access_key_id` 拼成 `accessKeyId`）
- 类型字段使用了不支持的值（支持：`access_key`、`sts`、`ecs_ram_role`、`ram_role_arn`、`oidc_role_arn`、`rsa_key_pair`）

---

#### Q6: 配置文件中的 profile 未找到

**问题现象**：
```
RuntimeException: Failed to get credential from CLI credentials file
```

**根本原因**：  
通过 `ALIBABA_CLOUD_PROFILE` 环境变量指定的profile名称在配置文件中不存在。代码会遍历所有profile寻找匹配的名称（`src/Providers/CLIProfileCredentialsProvider.php:82-143`）。

**解决方案**：
1. **检查profile名称**：
   ```bash
   cat ~/.aliyun/config.json | grep "name"
   ```

2. **确保环境变量与配置文件一致**：
   ```bash
   export ALIBABA_CLOUD_PROFILE=production
   ```
   配置文件中必须有名为 `production` 的profile

3. **使用默认profile**：
   - INI格式使用 `[default]` section
   - JSON格式在 `current` 字段指定默认profile名

---

### 4.3 ECS 实例角色相关问题

#### Q7: 提示 "The role was not found in the instance"

**问题现象**：
```
InvalidArgumentException: The role was not found in the instance
```

**根本原因**：  
尝试从ECS元数据服务获取实例角色凭证时，返回404错误。可能原因（代码位置 `src/Providers/EcsRamRoleCredentialsProvider.php:143-145`）：
- 当前不在ECS/ECI实例环境中运行
- ECS实例未绑定RAM角色
- 指定的 `roleName` 与实际绑定的角色名不符

**解决方案**：
1. **确认运行环境**：
   ```bash
   curl -s http://100.100.100.200/latest/meta-data/
   ```
   如超时或无法访问，说明不在ECS实例内

2. **检查实例是否绑定角色**：
   - 登录阿里云控制台 → ECS → 实例详情 → 查看"实例RAM角色"
   - 或通过元数据查询：
     ```bash
     curl http://100.100.100.200/latest/meta-data/ram/security-credentials/
     ```

3. **绑定RAM角色**：
   ```bash
   # 使用阿里云CLI
   aliyun ecs AttachInstanceRamRole --InstanceIds '["i-xxxx"]' --RamRoleName MyRole
   ```

4. **不指定roleName，让程序自动获取**：
   ```php
   $config = new Config([
       'type' => 'ecs_ram_role',
       // 不设置 roleName
   ]);
   ```

---

#### Q8: IMDSv2 加固模式失败，提示 "Failed to get token from ECS Metadata Service"

**问题现象**：
```
RuntimeException: Failed to get token from ECS Metadata Service. HttpCode=404
```

**根本原因**：  
代码默认先尝试使用IMDSv2获取元数据令牌（`src/Providers/EcsRamRoleCredentialsProvider.php:216-233`）。如果实例元数据服务不支持IMDSv2（老版本镜像），且设置了 `disableIMDSv1 = true`，会直接抛出异常而不降级到IMDSv1。

**解决方案**：
1. **允许降级到IMDSv1**（默认行为）：
   ```php
   $config = new Config([
       'type' => 'ecs_ram_role',
       'disableIMDSv1' => false,  // 允许降级
   ]);
   ```

2. **升级实例以支持IMDSv2**：
   - 确保使用最新的系统镜像
   - 参考文档：[实例元数据](https://help.aliyun.com/document_detail/49122.html)

3. **通过环境变量控制**：
   ```bash
   export ALIBABA_CLOUD_IMDSV1_DISABLED=false
   ```

---

#### Q9: 元数据服务超时

**问题现象**：  
程序长时间无响应或超时错误

**根本原因**：  
元数据服务请求的默认超时时间为1秒（connectTimeout和readTimeout均为1秒，见 `src/Providers/EcsRamRoleCredentialsProvider.php:55-60`）。在网络拥堵或实例性能受限时可能不足。

**解决方案**：
1. **增加超时时间**：
   ```php
   $config = new Config([
       'type' => 'ecs_ram_role',
       'connectTimeout' => 5,  // 连接超时5秒
       'readTimeout' => 5,     // 读取超时5秒
   ]);
   ```

2. **检查实例网络连通性**：
   ```bash
   time curl -s http://100.100.100.200/latest/meta-data/
   ```

---

#### Q10: 环境变量 ALIBABA_CLOUD_ECS_METADATA_DISABLED 导致无法使用

**问题现象**：
```
RuntimeException: IMDS credentials is disabled
```

**根本原因**：  
设置了 `ALIBABA_CLOUD_ECS_METADATA_DISABLED=true` 环境变量会完全禁用实例元数据服务凭证获取（代码 `src/Providers/EcsRamRoleCredentialsProvider.php:123-125`）。

**解决方案**：
1. **删除该环境变量**：
   ```bash
   unset ALIBABA_CLOUD_ECS_METADATA_DISABLED
   ```

2. **或设置为false**：
   ```bash
   export ALIBABA_CLOUD_ECS_METADATA_DISABLED=false
   ```

---

### 4.4 RAM 角色扮演（RamRoleArn）相关问题

#### Q11: 提示 "roleArn cannot be empty"

**问题现象**：
```
InvalidArgumentException: roleArn cannot be empty
```

**根本原因**：  
使用 `ram_role_arn` 类型时，必须提供 `roleArn` 参数。Filter校验（`src/Utils/Filter.php:121-126`）会确保该值非空。

**解决方案**：
1. **显式指定roleArn**：
   ```php
   $config = new Config([
       'type' => 'ram_role_arn',
       'accessKeyId' => 'xxx',
       'accessKeySecret' => 'xxx',
       'roleArn' => 'acs:ram::123456789012****:role/testrole',
       'roleSessionName' => 'session-name',
   ]);
   ```

2. **通过环境变量设置**：
   ```bash
   export ALIBABA_CLOUD_ROLE_ARN="acs:ram::123456789012****:role/testrole"
   ```

3. **检查roleArn格式**：
   - 正确格式：`acs:ram::{AccountID}:role/{RoleName}`
   - 获取方式：RAM控制台 → 角色管理 → 查看角色详情

---

#### Q12: AssumeRole 调用失败，权限不足

**问题现象**：  
STS API调用返回错误（通常是403或特定错误码）

**根本原因**：
- 用于调用AssumeRole的AccessKey无权限
- RAM角色的信任策略未配置正确
- 角色ARN不存在或拼写错误

**解决方案**：
1. **确保AccessKey有权限**：
   在RAM控制台为用户添加策略：
   ```json
   {
     "Version": "1",
     "Statement": [
       {
         "Effect": "Allow",
         "Action": "sts:AssumeRole",
         "Resource": "acs:ram::123456789012****:role/testrole"
       }
     ]
   }
   ```

2. **配置角色信任策略**：
   在RAM角色的信任策略中允许当前用户/服务扮演：
   ```json
   {
     "Statement": [
       {
         "Action": "sts:AssumeRole",
         "Effect": "Allow",
         "Principal": {
           "RAM": [
             "acs:ram::123456789012****:root"
           ]
         }
       }
     ],
     "Version": "1"
   }
   ```

3. **启用STS请求日志**：
   ```bash
   export DEBUG=sdk
   ```
   查看详细的请求和响应信息

---

#### Q13: Policy参数导致权限受限

**问题现象**：  
获取凭证成功，但使用凭证调用API时提示权限不足

**根本原因**：  
在 RamRoleArn 配置中设置了 `policy` 参数，进一步限制了临时凭证的权限。该策略会与角色权限策略取交集（代码 `src/Providers/RamRoleArnCredentialsProvider.php:83`）。

**解决方案**：
1. **移除policy参数**（使用角色的完整权限）：
   ```php
   $config = new Config([
       'type' => 'ram_role_arn',
       // 不设置 policy
   ]);
   ```

2. **检查policy JSON格式**：
   ```json
   {
     "Version": "1",
     "Statement": [
       {
         "Effect": "Allow",
         "Action": ["oss:GetObject", "oss:PutObject"],
         "Resource": ["acs:oss:*:*:mybucket/*"]
       }
     ]
   }
   ```

---

### 4.5 OIDC 相关问题

#### Q14: 提示 "oidcTokenFilePath cannot be empty"

**问题现象**：
```
InvalidArgumentException: oidcTokenFilePath cannot be empty
```

**根本原因**：  
使用 `oidc_role_arn` 类型时，必须提供OIDC令牌文件路径（Filter校验 `src/Utils/Filter.php:141-146`）。

**解决方案**：
1. **在Kubernetes RRSA场景**：
   容器服务会自动注入以下环境变量：
   ```bash
   export ALIBABA_CLOUD_OIDC_PROVIDER_ARN="acs:ram::123456789012****:oidc-provider/ack-rrsa-provider"
   export ALIBABA_CLOUD_OIDC_TOKEN_FILE="/var/run/secrets/tokens/oidc-token"
   export ALIBABA_CLOUD_ROLE_ARN="acs:ram::123456789012****:role/myrole"
   ```
   使用默认凭证链即可自动获取

2. **手动指定**：
   ```php
   $config = new Config([
       'type' => 'oidc_role_arn',
       'oidcProviderArn' => 'acs:ram::xxx:oidc-provider/xxx',
       'oidcTokenFilePath' => '/path/to/token/file',
       'roleArn' => 'acs:ram::xxx:role/xxx',
       'roleSessionName' => 'session',
   ]);
   ```

---

#### Q15: OIDC Token 文件读取失败

**问题现象**：  
文件不存在或无读权限

**根本原因**：  
OIDC令牌文件由Kubernetes自动挂载（通常为投影卷），如果Pod配置错误或文件权限不当会导致读取失败。

**解决方案**：
1. **检查文件是否存在**：
   ```bash
   ls -la /var/run/secrets/tokens/oidc-token
   ```

2. **验证Pod配置**：
   确保在Pod spec中配置了serviceAccountName，并且ServiceAccount关联了RRSA annotation

3. **检查令牌内容**：
   ```bash
   cat /var/run/secrets/tokens/oidc-token
   ```
   应为有效的JWT格式

---

### 4.6 凭证URI相关问题

#### Q16: 提示 "credentialsURI cannot be empty"

**问题现象**：
```
InvalidArgumentException: credentialsURI cannot be empty
```

**根本原因**：  
使用 `credentials_uri` 类型时未提供URI地址（Filter校验 `src/Utils/Filter.php:213-222`）。

**解决方案**：
1. **通过环境变量设置**：
   ```bash
   export ALIBABA_CLOUD_CREDENTIALS_URI="http://localhost:8080/credentials"
   ```

2. **显式配置**：
   ```php
   $config = new Config([
       'type' => 'credentials_uri',
       'credentialsURI' => 'http://your-server/credentials',
   ]);
   ```

---

#### Q17: URI服务返回格式错误

**问题现象**：
```
RuntimeException: Error retrieving credentials from credentialsURI result
```

**根本原因**：  
凭证URI服务返回的JSON格式不符合要求。代码期望包含 `AccessKeyId`、`AccessKeySecret`、`SecurityToken`、`Expiration` 字段（`src/Providers/URLCredentialsProvider.php:97-99`）。

**解决方案**：
1. **确保返回正确的JSON格式**：
   ```json
   {
     "Code": "Success",
     "AccessKeyId": "STS.xxx",
     "AccessKeySecret": "xxx",
     "SecurityToken": "xxx",
     "Expiration": "2024-12-07T12:00:00Z"
   }
   ```

2. **检查HTTP状态码**：
   必须返回200状态码

3. **测试URI可访问性**：
   ```bash
   curl -v http://your-server/credentials
   ```

---

### 4.7 默认凭证提供链相关问题

#### Q18: 提示 "Unable to load credentials from any of the providers in the chain"

**问题现象**：
```
RuntimeException: Unable to load credentials from any of the providers in the chain: 
EnvironmentVariableCredentialsProvider: Access key ID must be specified via environment variable, 
ProfileCredentialsProvider: Credentials file is not readable: /home/user/.alibabacloud/credentials
```

**根本原因**：  
使用默认凭证提供链时，所有提供者都失败了。代码会依次尝试：
1. 环境变量
2. OIDC（如果相关环境变量存在）
3. CLI配置文件（`~/.aliyun/config.json`）
4. 凭证配置文件（`~/.alibabacloud/credentials`）
5. ECS实例角色
6. 凭证URI（如果环境变量存在）

**解决方案**：
1. **查看详细错误信息**：
   异常消息会列出每个提供者的失败原因

2. **至少配置一种凭证来源**：
   - 最简单：设置环境变量
   - 推荐：创建配置文件
   - 生产环境：使用实例角色

3. **手动测试每个来源**：
   ```php
   // 测试环境变量
   var_dump(getenv('ALIBABA_CLOUD_ACCESS_KEY_ID'));
   
   // 测试配置文件
   if (file_exists(getenv('HOME') . '/.alibabacloud/credentials')) {
       echo "Config file exists\n";
   }
   ```

---

#### Q19: 凭证提供链顺序导致非预期的凭证被使用

**问题现象**：  
预期使用配置文件凭证，但实际使用了环境变量凭证

**根本原因**：  
默认凭证提供链有固定优先级：环境变量 > OIDC > CLI配置 > 凭证文件 > 实例角色 > URI（代码 `src/Providers/DefaultCredentialsProvider.php:59-92`）。

**解决方案**：
1. **清理高优先级的凭证来源**：
   ```bash
   unset ALIBABA_CLOUD_ACCESS_KEY_ID
   unset ALIBABA_CLOUD_ACCESS_KEY_SECRET
   ```

2. **显式指定凭证类型，避免使用默认链**：
   ```php
   $config = new Config([
       'type' => 'access_key',
       'accessKeyId' => 'from-config-file',
       'accessKeySecret' => 'xxx',
   ]);
   ```

3. **使用profile切换不同凭证**：
   ```bash
   export ALIBABA_CLOUD_PROFILE=production
   ```

---

### 4.8 凭证过期与刷新问题

#### Q20: 临时凭证过期，未自动刷新

**问题现象**：  
使用STS、RamRoleArn等临时凭证时，出现"SecurityToken已过期"错误

**根本原因**：  
虽然库支持自动刷新，但需要注意：
- 凭证对象被缓存但未调用 `getCredential()` 触发刷新检查
- 刷新逻辑仅在调用时执行（`src/Providers/SessionCredentialsProvider.php`）
- 并发场景下可能存在竞态条件

**解决方案**：
1. **每次使用前重新获取凭证**：
   ```php
   // 不推荐：长时间持有凭证对象
   $cred = $credential->getCredential();
   sleep(3600);
   $cred->getAccessKeyId();  // 可能已过期
   
   // 推荐：每次使用时获取
   function callApi() {
       global $credential;
       $cred = $credential->getCredential();  // 会自动检查并刷新
       // 使用凭证调用API
   }
   ```

2. **配置合适的过期时间**：
   ```php
   $config = new Config([
       'type' => 'ram_role_arn',
       'roleSessionExpiration' => 3600,  // 1小时
   ]);
   ```

3. **启用凭证缓存复用**：
   ```php
   $provider = new DefaultCredentialsProvider([
       'reuseLastProviderEnabled' => true,  // 默认即为true
   ]);
   ```

---

### 4.9 类型与参数校验问题

#### Q21: 提示 "Unsupported credential type option"

**问题现象**：
```
InvalidArgumentException: Unsupported credential type option: xxx, 
support: access_key, sts, bearer, ecs_ram_role, ram_role_arn, rsa_key_pair, oidc_role_arn, credentials_uri
```

**根本原因**：  
`type` 参数拼写错误或使用了不支持的值（代码 `src/Credential.php:176-177`）。

**解决方案**：
检查并使用正确的类型名称：
- `access_key`：静态AccessKey
- `sts`：静态STS凭证
- `bearer`：Bearer Token
- `ecs_ram_role`：ECS实例角色
- `ram_role_arn`：RAM角色扮演
- `rsa_key_pair`：RSA密钥对（仅日本站）
- `oidc_role_arn`：OIDC角色扮演
- `credentials_uri`：凭证URI

**注意大小写和下划线**（如 `access_key` 不能写成 `accessKey`）

---

#### Q22: 参数类型错误

**问题现象**：
```
InvalidArgumentException: connectTimeout must be a int
InvalidArgumentException: disableIMDSv1 must be a boolean
```

**根本原因**：  
Filter类对所有参数进行类型校验（`src/Utils/Filter.php`）。例如，将字符串 `"10"` 传给 `connectTimeout` 会报错。

**解决方案**：
确保参数类型正确：
```php
// 错误
$config = new Config([
    'connectTimeout' => "10",        // 字符串
    'disableIMDSv1' => "true",       // 字符串
]);

// 正确
$config = new Config([
    'connectTimeout' => 10,          // 整数
    'disableIMDSv1' => true,         // 布尔
]);
```

---

### 4.10 网络与超时问题

#### Q23: 连接 STS 服务超时

**问题现象**：  
GuzzleException 或 cURL超时错误

**根本原因**：
- 网络限制（防火墙、安全组）
- STS服务地址不可达
- 默认超时时间（5秒）不足

**解决方案**：
1. **检查网络连通性**：
   ```bash
   curl -v https://sts.aliyuncs.com
   ```

2. **增加超时时间**：
   ```php
   $config = new Config([
       'type' => 'ram_role_arn',
       'connectTimeout' => 10,
       'readTimeout' => 10,
   ]);
   ```

3. **使用VPC内网地址**（仅限阿里云VPC内）：
   ```php
   $config = new Config([
       'type' => 'ram_role_arn',
       'STSEndpoint' => 'sts-vpc.cn-hangzhou.aliyuncs.com',
   ]);
   ```

4. **检查防火墙规则**：
   确保允许出站HTTPS（443端口）访问

---

#### Q24: SSL/TLS 证书验证失败

**问题现象**：
```
cURL error 60: SSL certificate problem: unable to get local issuer certificate
```

**根本原因**：  
系统缺少CA证书包，无法验证阿里云服务器证书

**解决方案**：
1. **安装CA证书包**（推荐）：
   ```bash
   # Ubuntu/Debian
   sudo apt-get install ca-certificates
   
   # CentOS/RHEL
   sudo yum install ca-certificates
   ```

2. **下载并配置证书包**：
   ```bash
   curl https://curl.se/ca/cacert.pem -o /etc/ssl/certs/cacert.pem
   ```
   
   在 php.ini 中配置：
   ```ini
   curl.cainfo = "/etc/ssl/certs/cacert.pem"
   openssl.cafile = "/etc/ssl/certs/cacert.pem"
   ```

3. **临时禁用SSL验证**（仅用于测试，不推荐生产环境）：
   需修改Guzzle配置（本库未直接提供该选项）

---

### 4.11 多线程/并发问题

#### Q25: 多进程/多线程环境下凭证混乱

**问题现象**：  
在PHP-FPM、Swoole等并发场景下，不同请求获取到相同的凭证或出现竞态条件

**根本原因**：  
凭证对象和缓存可能在进程/协程间共享

**解决方案**：
1. **每个请求创建独立的Credential实例**：
   ```php
   // 避免全局单例
   function handleRequest() {
       $credential = new Credential();  // 每次创建新实例
       // 使用凭证
   }
   ```

2. **关闭缓存复用**（如有并发需求）：
   ```php
   $provider = new DefaultCredentialsProvider([
       'reuseLastProviderEnabled' => false,
   ]);
   ```

3. **在Swoole中重置凭证**：
   ```php
   $server->on('workerStart', function($server, $workerId) {
       // 清理静态缓存
       DefaultCredentialsProvider::flush();
   });
   ```

---

### 4.12 权限与安全问题

#### Q26: AccessKey 泄露风险

**问题现象**：  
AccessKey硬编码在代码中或日志中被打印

**根本原因**：  
不当的使用方式导致敏感信息暴露

**解决方案**：
1. **永不硬编码凭证**：
   ```php
   // 错误：硬编码
   $config = new Config([
       'accessKeyId' => 'LTAI4xxx',  // 不要这样做
   ]);
   
   // 正确：使用环境变量或配置文件
   $credential = new Credential();
   ```

2. **配置文件权限管理**：
   ```bash
   chmod 600 ~/.alibabacloud/credentials
   ```

3. **使用临时凭证**：
   优先使用STS、实例角色等临时凭证方案

4. **定期轮换密钥**：
   定期更新AccessKey

5. **避免在日志中打印凭证**：
   ```php
   // 错误
   error_log("Using key: " . $cred->getAccessKeyId());
   
   // 正确：脱敏
   $maskedKey = substr($cred->getAccessKeyId(), 0, 4) . '****';
   error_log("Using key: " . $maskedKey);
   ```

---

#### Q27: 最小权限原则未遵循

**问题现象**：  
使用主账号AccessKey或权限过大的子账号

**根本原因**：  
安全意识不足

**解决方案**：
1. **使用RAM子账号**：
   - 创建专用子账号
   - 仅授予必需的权限
   - 示例（OSS只读权限）：
     ```json
     {
       "Version": "1",
       "Statement": [
         {
           "Effect": "Allow",
           "Action": "oss:GetObject",
           "Resource": "acs:oss:*:*:mybucket/*"
         }
       ]
     }
     ```

2. **使用角色扮演+Policy**：
   ```php
   $config = new Config([
       'type' => 'ram_role_arn',
       'policy' => json_encode([
           'Version' => '1',
           'Statement' => [
               [
                   'Effect' => 'Allow',
                   'Action' => 'oss:GetObject',
                   'Resource' => 'acs:oss:*:*:mybucket/*',
               ]
           ]
       ]),
   ]);
   ```

---

### 4.13 兼容性问题

#### Q28: PHP 版本过低

**问题现象**：
```
Parse error: syntax error, unexpected T_FUNCTION
```

**根本原因**：  
PHP版本低于5.6，不支持代码中的语法特性

**解决方案**：
1. **升级PHP版本至5.6+**：
   ```bash
   # Ubuntu
   sudo apt-get install php7.4
   
   # CentOS
   sudo yum install php74
   ```

2. **检查当前版本**：
   ```bash
   php -v
   ```

---

#### Q29: 缺少必需的PHP扩展

**问题现象**：
```
Fatal error: Call to undefined function curl_init()
```

**根本原因**：  
未安装必需的PHP扩展（curl、json、openssl等）

**解决方案**：
1. **检查已安装扩展**：
   ```bash
   php -m
   ```

2. **安装缺失扩展**：
   ```bash
   # Ubuntu/Debian
   sudo apt-get install php-curl php-json php-xml php-mbstring
   
   # CentOS/RHEL
   sudo yum install php-curl php-json php-xml php-mbstring
   ```

3. **验证扩展加载**：
   ```php
   <?php
   if (!extension_loaded('curl')) {
       die('curl extension not loaded');
   }
   ```

---

### 4.14 调试与日志

#### Q30: 如何启用详细日志以排查问题

**问题现象**：  
遇到问题但不知道具体哪里出错

**解决方案**：
1. **启用SDK调试模式**：
   ```bash
   export DEBUG=sdk
   ```
   会打印HTTP请求和响应详情

2. **捕获并打印详细异常**：
   ```php
   try {
       $credential = new Credential();
       $cred = $credential->getCredential();
   } catch (\Exception $e) {
       echo "Exception Type: " . get_class($e) . "\n";
       echo "Message: " . $e->getMessage() . "\n";
       echo "Trace:\n" . $e->getTraceAsString() . "\n";
   }
   ```

3. **使用 var_dump 检查中间状态**：
   ```php
   var_dump(getenv('ALIBABA_CLOUD_ACCESS_KEY_ID'));
   var_dump(file_exists(getenv('HOME') . '/.alibabacloud/credentials'));
   ```

4. **检查 Guzzle 请求历史**（需修改代码启用）：
   使用 Guzzle 的 History 中间件记录所有HTTP请求

---

## 5. 最佳实践建议

### 5.1 开发环境
- 使用环境变量或配置文件，便于快速切换凭证
- 设置 `DEBUG=sdk` 查看详细请求信息

### 5.2 测试环境
- 使用独立的测试账号AccessKey或RAM角色
- 限制测试凭证权限范围
- 定期轮换测试凭证

### 5.3 生产环境
- **优先使用实例角色或OIDC**（无密钥部署）
- 永不在代码中硬编码凭证
- 配置文件权限设为600
- 使用RAM子账号，遵循最小权限原则
- 启用STS临时凭证，缩短有效期
- 配置合理的超时时间（connectTimeout、readTimeout）
- 监控凭证获取失败率和延迟

### 5.4 性能优化
- 启用凭证缓存（`reuseLastProviderEnabled=true`）
- 避免频繁创建Credential实例
- 使用VPC内网端点减少公网延迟
- 适当增加超时时间以应对网络抖动

### 5.5 安全加固
- 定期审计RAM权限
- 启用CloudTrail记录API调用日志
- 对于ECS，使用IMDSv2加固模式（`disableIMDSv1=true`）
- 使用HTTPS，确保CA证书有效

---

## 6. 常用命令速查

```bash
# 检查PHP版本和扩展
php -v
php -m | grep -E "curl|json|openssl"

# 测试元数据服务（仅ECS内部）
curl http://100.100.100.200/latest/meta-data/
curl http://100.100.100.200/latest/meta-data/ram/security-credentials/

# 查看环境变量
env | grep ALIBABA_CLOUD

# 测试STS连通性
curl -v https://sts.aliyuncs.com

# 检查配置文件
cat ~/.alibabacloud/credentials
cat ~/.aliyun/config.json

# 测试凭证获取
php -r "require 'vendor/autoload.php'; \$c=new AlibabaCloud\Credentials\Credential(); var_dump(\$c->getCredential());"
```

---

## 7. 参考资源

- **官方文档**：[Alibaba Cloud Credentials for PHP](https://github.com/aliyun/credentials-php)
- **RAM角色管理**：[RAM控制台](https://ram.console.aliyun.com/)
- **STS服务**：[STS API文档](https://help.aliyun.com/document_detail/28756.html)
- **RRSA（容器服务）**：[使用RRSA授权不同Pod访问不同云服务](https://help.aliyun.com/document_detail/2142941.html)
- **实例元数据**：[ECS实例元数据](https://help.aliyun.com/document_detail/49122.html)
- **问题反馈**：[GitHub Issues](https://github.com/aliyun/credentials-php/issues)

---

**文档版本**：v1.0  
**最后更新**：2025-12-07  
**适用库版本**：alibabacloud/credentials >= 1.1.5
