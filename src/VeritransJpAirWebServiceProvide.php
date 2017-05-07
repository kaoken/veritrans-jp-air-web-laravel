<?php
/**
 * Copyright (c) 2017 kaoken
 *
 * This software is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 */
namespace Kaoken\VeritransJpAirWeb;

use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Application as FoundationApplication;
use Laravel\Lumen\Application as LumenApplication;



class VeritransJpAirWebServiceProvide extends ServiceProvider
{
    /**
     * 決済指定なし
     * @var string
     */
    const SETTLEMENT_NONE = '00';
    /**
     * カード決済
     * @var string
     */
    const SETTLEMENT_CARD = '01';
    /**
     * コンビニ決済
     * @var string
     */
    const SETTLEMENT_CVS = '02';
    /**
     * @var bool
     */
    protected $defer = true;

    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->setupConfig();

    }

    /**
     * app/config/markdownit.php
     */
    protected function setupConfig()
    {
        $path = config_path('/veritrans-jp-air-web.php');

        if ( $this->app instanceof FoundationApplication && $this->app->runningInConsole() && !file_exists($path) ) {
            $srcPath=__DIR__.'/Config/config.php';
            $this->publishes([$srcPath => $path]);
        } else if ($this->app instanceof LumenApplication) {
            $this->app->configure('veritrans-jp-air-web');
        }

        $this->mergeConfigFrom($path, 'veritrans-jp-air-web');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('veritrans-jp-air-web', function ($app) {
            return new VeritransJpAirWebManager($app);
        });
    }



    /**
     * Get the services provided by the provider.
     *
     * @return string[]
     */
    public function provides()
    {
        return [
            'veritrans-jp-air-web'
        ];
    }
}
