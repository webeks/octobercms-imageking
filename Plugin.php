<?php namespace Code200\ImageKing;

use System\Classes\PluginBase;
use System\Classes\SettingsManager;


class Plugin extends PluginBase
{

    /**
     * Boot method, called right before the request route.
     *
     * @return array
     */
    public function boot()
    {
        $this->app['Illuminate\Contracts\Http\Kernel']
            ->pushMiddleware('Code200\ImageKing\Classes\ImageKingMiddleware');
    }

    public function registerSettings()
    {
        return [
            'settings' => [
                'label'       => 'code200.imageking::lang.settings.label',
                'description' => 'code200.imageking::lang.settings.description',
                'icon'        => 'oc-icon-cubes',
                'class'       => 'Code200\imageking\models\Settings',
                'category'    => SettingsManager::CATEGORY_SYSTEM
            ]
        ];
    }
}
