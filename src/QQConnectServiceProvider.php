<?php

namespace Superzc\QQConnect;

use Illuminate\Support\ServiceProvider;

class QQConnectServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        // 发布配置文件
        $this->publishes([
            __DIR__ . '/config/qqconnect.php' => config_path('qqconnect.php'),
        ]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('qqconnect', function ($app, $params) {
            return new QQConnect($params['openid'], $params['access_token']);
        });
    }

    public static function create($openid, $access_token)
    {
        return new self($openid, $access_token);
    }
}
