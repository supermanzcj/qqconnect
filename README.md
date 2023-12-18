# QQConnect Extension Pack

[![Latest Version on Packagist](https://img.shields.io/packagist/v/superzc/qqconnect.svg?style=flat-square)](https://packagist.org/packages/superzc/qqconnect)
[![Release Version](https://img.shields.io/badge/release-1.0.0-red.svg)](https://github.com/supermanzcj/qqconnect/releases)

This package provides additional features to the Laravel framework.


## Installation

You can install the package via composer:

```bash
composer require superzc/qqconnect
```

## Usage

修改QQ互联配置文件`config/qqconnect.php`
```php
return [
    'appid' => 'xxx',
    'appkey' => 'xxx',
    'callback' => 'https://example.com/qc_redirect.html',
];
```

调用类方法
```php
use Superzc\QQConnect\QQConnect;
use Superzc\QQConnect\Exceptions\DefaultException as QCException;

try {
    $qqconnect = new QQConnect();
    $qqconnect->init($openid, $access_token);
    $result = $qqconnect->doSomething();
} catch (MPDefaultException $e) {
    return response()->json([
        'ret' => $e->getCode(),
        'msg' => $e->getMessage(),
    ]);
}
```

使用门面
```php
use Superzc\QQConnect\Facades\QQConnect;

try {
    QQConnect::init($openid, $access_token);
    $result = QQConnect::doSomething();
} catch (QCException $e) {
    return response()->json([
        'ret' => $e->getCode(),
        'msg' => $e->getMessage(),
    ]);
}
```

## Change log
暂无