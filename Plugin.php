<?php namespace Nsrosenqvist\ThemesPlus;

use App;
use File;
use Cms\Classes\Theme;
use System\Classes\ComposerManager;
use System\Classes\PluginManager;

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
        // Get active theme's directory
        $theme = Theme::getActiveTheme();
        $themePath = $theme->getPath();
        $vendorPath = $themePath.'/vendor';
        $providerPath = $themePath.'/Plugin.php';

        // Autoload active theme's vendor directory
        if ( File::isDirectory($vendorPath) )
            ComposerManager::instance()->autoload($vendorPath);

        // Load your theme's Theme.php file as a service provider
        if ( File::exists($providerPath) )
        {
            require $providerPath;

            if ( class_exists('ThemesPlusTheme\\Plugin'))
            {
                //\App::register('\\ThemesPlusTheme\\Plugin');

                $manager = PluginManager::instance();

                // Add our service provider to the plugin manager
                $manager->loadPlugin('ThemesPlusTheme', $themePath);

                // Boot it
                $plugins = $manager->getPlugins();
                $manager->bootPlugin($plugins['ThemesPlusTheme.Plugin']);
            }
        }

    }
}
