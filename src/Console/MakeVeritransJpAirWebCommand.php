<?php

namespace Kaoken\VeritransJpAirWeb\Console;

use Illuminate\Console\Command;
use Illuminate\Console\DetectsApplicationNamespace;

class MakeVeritransJpAirWebCommand extends Command
{
    use DetectsApplicationNamespace;

    /**
     * コンソールコマンドの名前と署名。
     *
     * @var string
     */
    protected $signature = 'veritrans-jp:web-air:install';

    /**
     * コンソールコマンドの説明。
     *
     * @var string
     */
    protected $description = 'Veritrans Jp:web-air のインストールをします。';

    /**
     * エクスポートする必要があるマイグレーション。
     *
     * @var [string]
     */
    protected $migrationss = [
        '2017_04_24_000000_create_air_web_commodity_regist_table.stub' => '2017_04_24_000000_create_air_web_commodity_regist_table.php',
        '2017_04_24_000001_create_air_web_commodity_table.stub' => '2017_04_24_000001_create_air_web_commodity_table.php',
        '2017_04_24_000002_create_air_web_payment_notification_table.stub' => '2017_04_24_000002_create_air_web_payment_notification_table.php',
        '2017_04_24_000003_create_air_web_cvs_payment_notification_table.stub' => '2017_04_24_000003_create_air_web_cvs_payment_notification_table.php'
    ];

    /**
     * エクスポートする必要があるシーダー。
     *
     * @var [string]
     */
    protected $seeds = [
        'VeritransJpAirWebSeeder.stub' => 'VeritransJpAirWebSeeder.php'
    ];

    /**
     * コンソールコマンドを実行します。
     *
     * @return void
     */
    public function fire()
    {
        $this->createDirectories();

        $this->exportMigrationss();
        $this->exportSeeds();
        $this->exportConfig();

        $this->info('Veritrans Jp:web-airのインストールを終了しました。');
    }

    /**
     * ファイルのディレクトリを作成します。
     *
     * @return void
     */
    protected function createDirectories()
    {

    }

    /**
     * マイグレーションをエクスポートします。
     *
     * @return void
     */
    protected function exportMigrationss()
    {
        foreach ($this->migrationss as $key => $value) {
            if (file_exists(database_path('migrations/'.$value)) ) {
                if (! $this->confirm("マイグレーションファイル [{$value}] は既に存在します。 上書きしますか？?")) {
                    continue;
                }
            }
            copy(
                __DIR__.'/stubs/database/migrations/'.$key,
                database_path('migrations/'.$value)
            );
        }
    }

    /**
     * シーダーをエクスポートします。
     *
     * @return void
     */
    protected function exportSeeds()
    {
        foreach ($this->seeds as $key => $value) {
            if (file_exists(database_path('seeds/'.$value)) ) {
                if (! $this->confirm("シーダーファイル [{$value}] は既に存在します。 上書きしますか？")) {
                    continue;
                }
            }
            copy(
                __DIR__.'/stubs/database/seeds/'.$key,
                database_path('seeds/'.$value)
            );
        }
    }

    /**
     * コンフィグをエクスポートします。
     *
     * @return void
     */
    protected function exportConfig()
    {
        if (file_exists(config_path('veritrans-jp-air-web.php')) ) {
            if (! $this->confirm("コンフィグファイル [veritrans-jp-air-web.php] は既に存在します。 上書きしますか？")) {
                return;
            }
        }
        copy(
            __DIR__.'/stubs/config.stub',
            config_path('veritrans-jp-air-web.php')
        );
    }

}
