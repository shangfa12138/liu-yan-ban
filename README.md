## 一、项目概述

尚法留言板是一个基于 PHP + MySQL 开发的轻量级留言系统，支持用户注册、登录、留言、头像上传、密码修改等功能，并提供管理员后台进行用户和内容管理。

## 二、技术栈

| 分类 | 技术 | 版本 |
| :--- | :--- | :--- |
| 后端语言 | PHP | 8.3+ |
| 数据库 | MySQL | 5.7+ |
| 数据库驱动 | PDO | - |
| 前端 | HTML/CSS | - |

## 三、项目结构

```
无css版/
├── index.php              # 入口文件（重定向到登录页）
├── login.php              # 登录页面
├── 注册页面.php           # 用户注册页面
├── message_board.php      # 留言板主页面
├── admin.php              # 管理员后台
├── logout.php             # 登出处理
├── 重置密码.php           # 密码重置页面
├── check.php              # 用户登录状态检查
├── pdo_connect.php        # 数据库连接配置
├── 清洁函数.php           # 输入安全清理函数
├── ensure_liuyanban_append.php  # 留言追加表初始化
├── ensure_username_avatar.php   # 用户头像字段初始化
├── image_upload_store.php       # 图片上传存储处理
├── user_content_purge.php       # 用户内容清理
├── style.css              # 样式文件
├── upload/                # 图片上传目录
└── .gitignore.txt         # Git忽略配置
```

## 四、核心功能

### 4.1 用户认证

| 功能 | 说明 | 文件 |
| :--- | :--- | :--- |
| 用户注册 | 支持用户名、邮箱、密码注册 | 注册页面.php |
| 用户登录 | 支持用户名/邮箱登录，可选记住用户名 | login.php |
| 密码重置 | 通过邮箱重置密码 | 重置密码.php |
| 用户登出 | 清除session退出登录 | logout.php |

### 4.2 留言功能

| 功能 | 说明 | 文件 |
| :--- | :--- | :--- |
| 发表留言 | 用户登录后可发表留言 | message_board.php |
| 追加留言 | 可在已有留言下追加内容（回复功能） | message_board.php |
| 删除留言 | 作者和管理员可删除留言 | message_board.php |
| 头像上传 | 支持JPG/PNG格式头像上传 | message_board.php |
| 密码修改 | 用户可修改自己的密码 | message_board.php |

### 4.3 管理员功能

| 功能 | 说明 | 文件 |
| :--- | :--- | :--- |
| 用户管理 | 查看、修改用户角色、删除用户 | admin.php |
| 留言管理 | 查看、编辑、删除所有留言 | admin.php |
| 图片上传 | 管理员可上传图片文件 | admin.php |

## 五、数据库设计

### 5.1 用户表 `username`

| 字段 | 类型 | 说明 |
| :--- | :--- | :--- |
| id | INT | 用户ID（主键，自增） |
| name | VARCHAR(50) | 用户名（唯一） |
| password | VARCHAR(255) | 密码（加密存储） |
| email | VARCHAR(100) | 邮箱（唯一） |
| role | VARCHAR(20) | 角色（user/admin） |
| avatar | VARCHAR(255) | 头像路径 |
| created_at | DATETIME | 创建时间 |

### 5.2 留言表 `liuyanban`

| 字段 | 类型 | 说明 |
| :--- | :--- | :--- |
| id | INT | 留言ID（主键，自增） |
| name | VARCHAR(50) | 留言者用户名 |
| user_id | INT | 留言者用户ID |
| content | TEXT | 留言内容 |
| created_at | DATETIME | 创建时间 |

### 5.3 留言追加表 `liuyanban_append`

| 字段 | 类型 | 说明 |
| :--- | :--- | :--- |
| id | INT | 追加ID（主键，自增） |
| message_id | INT | 关联留言ID |
| user_id | INT | 追加者用户ID |
| content | TEXT | 追加内容 |
| created_at | DATETIME | 创建时间 |

## 六、部署与运行

### 6.1 环境要求

- PHP 8.3+
- MySQL 5.7+
- 支持PDO扩展

### 6.2 数据库配置

修改 `pdo_connect.php` 配置数据库连接：

```php
$host = "localhost";
$dbname = "kaohe";
$user = "root";
$password = "root";
```

### 6.3 启动开发服务器

```bash
# 本地启动（仅本机访问）
php -S localhost:8000

# 局域网启动（允许局域网设备访问）
php -S 0.0.0.0:8000
```

### 6.4 访问地址

- 本地访问：`http://localhost:8000`
- 局域网访问：`http://[本机IP]:8000`

## 七、安全特性

| 特性 | 说明 |
| :--- | :--- |
| 密码加密 | 使用 password_hash() 加密存储 |
| SQL注入防护 | 使用 PDO 预编译语句 |
| XSS防护 | 使用自定义 superClean() 函数过滤输入输出 |
| 文件上传验证 | 验证文件类型、大小、内容安全性 |
| 会话管理 | 使用 PHP Session 管理登录状态 |
| 权限控制 | 管理员与普通用户权限分离 |

## 八、使用流程

```
1. 访问首页 → 自动重定向到登录页
2. 新用户点击「注册」→ 填写注册信息 → 注册成功
3. 用户登录 → 进入留言板
4. 在留言板发表留言、追加回复、上传头像、修改密码
5. 管理员登录 → 进入管理员后台进行管理操作
```

## 九、代码规范

- 文件名使用中文或英文命名
- 变量命名使用下划线命名法（如 `$user_id`）
- 函数命名使用 camelCase（如 `superClean()`）
- 使用 UTF-8 编码
- 使用 PDO 进行数据库操作
- 输入输出均经过安全过滤

## 十、注意事项

1. 确保 MySQL 服务已启动
2. 数据库连接配置正确（数据库名、用户名、密码）
3. `upload/` 目录需要可写权限
4. 默认管理员账号需手动在数据库创建（role 字段设为 `admin`）
5. 头像上传限制：JPG/PNG格式，最大约5MB
