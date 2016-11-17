<?php namespace Code200\ImageKing;

use Backend\Facades\BackendAuth;
use System\Classes\PluginBase;
use System\Classes\SettingsManager;


class Plugin extends PluginBase
{

    private $user;


    public function __construct(\Illuminate\Contracts\Foundation\Application $app)
    {
        parent::__construct($app);
    }

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
        $this->user = BackendAuth::getUser();
        if($this->user->hasAccess(['code200.imageking.modify_settings'])) {
            return  [
                'settings' => [
                    'label'       => 'code200.imageking::lang.settings.label',
                    'description' => 'code200.imageking::lang.settings.description',
                    'icon'        => 'oc-icon-cubes',
                    'class'       => 'Code200\imageking\models\Settings',
                    'category'    => SettingsManager::CATEGORY_SYSTEM
                ]
            ];
        }
        return false;
    }


    public function registerPermissions()
    {
        return [
            'code200.imageking.modify_settings' => [
                'label' => 'Modify Settings',
                'tab' => 'Code200 ImageKing'
            ]

        ];
    }

}
