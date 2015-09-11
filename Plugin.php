<?php namespace Nsrosenqvist\ThemesPlus;

use Nsrosenqvist\ThemesPlus\Classes\ThemePluginManager;

class Plugin extends \System\Classes\PluginBase
{
    public function pluginDetails()
    {
        return [
            'name' => 'Themes+',
            'description' => 'Enables theme developers to ship plugins with their themes.',
            'author' => 'Niklas Rosenqvist',
            'icon' => 'icon-leaf',
            'homepage' => 'https://www.nsrosenqvist.com/'
        ];
    }

    public function boot()
    {
        $themePluginManager = new ThemePluginManager();
        $themePluginManager->init();
    }
}
