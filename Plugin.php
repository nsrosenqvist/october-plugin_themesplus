<?php namespace Nsrosenqvist\ThemesPlus;

use App;
use File;
use Cms\Classes\Theme;
use System\Classes\ComposerManager;
use System\Classes\PluginManager;
use Nsrosenqvist\ThemesPlus\Models\Settings;

class Plugin extends \System\Classes\PluginBase
{
    private $manager;

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
        $this->manager = PluginManager::instance();

        // Get paths we need
        $theme = Theme::getActiveTheme();
        $themePath = $theme->getPath();
        $themeVendorPath = $themePath.'/dependencies/autoload.php';
        $pluginPath = dirname(__FILE__);
        $providerPath = $themePath.'/Plugin.php';

        // Load your theme's Theme.php file as a service provider
        if (File::exists($providerPath))
        {
            // Use reflection to find out info about Plugin.php
            $info = new Classes\ClassInfo($providerPath);

            if (ltrim($info->extends, '\\') == "Nsrosenqvist\\ThemesPlus\\Classes\\ThemesPlusBase")
            {
                // Activate the theme plugin
                $plugin = $this->manager->loadPlugin($info->namespace, $themePath);
                $identifier = $this->manager->getIdentifier($plugin);
                $definitionsFile = $pluginPath.'/composer/definitions.php';

                $this->manager->registerPlugin($plugin);
                $this->manager->bootPlugin($plugin);

                // See if we need to generate a new composer psr-4 definitions file
                if (Settings::get('definitions_generated_for') != $identifier || ! File::exists($definitionsFile))
                {
                    File::put($definitionsFile, $this->makeDefinitionFile($info->namespace, $themePath));
                    Settings::set('definitions_generated_for', $identifier);
                }

                // Autoload theme vendor directory
                if (File::isFile($themeVendorPath))
                {
                    ComposerManager::instance()->autoload($themeVendorPath);
                }

                // Add theme to autoload through our definitions file
                ComposerManager::instance()->autoload($pluginPath);
            }
        }

        // dd(ComposerManager::instance());
    }

    protected function makeDefinitionFile($namespace, $path)
    {
        $php = '<?php'.PHP_EOL;
        $php .= PHP_EOL;
        $php .= 'return array('.PHP_EOL;
        $php .= "\t".'\''.str_replace('\\', '\\\\', $namespace).'\\\\\' => array(\''.$path.'\')'.PHP_EOL;
        $php .= ');'.PHP_EOL;

        return $php;
    }

//     return array(
//     'XdgBaseDir\\' => array($vendorDir . '/dnoegel/php-xdg-base-dir/src'),
//     'System\\' => array($baseDir . '/modules/system'),
//     'Symfony\\Component\\Filesystem\\' => array($vendorDir . '/symfony/filesystem'),
//     'Symfony\\Component\\EventDispatcher\\' => array($vendorDir . '/symfony/event-dispatcher'),
//     'SuperClosure\\' => array($vendorDir . '/jeremeamia/SuperClosure/src'),
//     'Stringy\\' => array($vendorDir . '/danielstjules/stringy/src'),
//     'October\\Rain\\' => array($vendorDir . '/october/rain/src'),
//     'Monolog\\' => array($vendorDir . '/monolog/monolog/src/Monolog'),
//     'League\\Flysystem\\' => array($vendorDir . '/league/flysystem/src'),
//     'League\\Csv\\' => array($vendorDir . '/league/csv/src'),
//     'Illuminate\\Html\\' => array($vendorDir . '/illuminate/html'),
//     'Illuminate\\' => array($vendorDir . '/laravel/framework/src/Illuminate'),
//     'Cms\\' => array($baseDir . '/modules/cms'),
//     'ClassPreloader\\' => array($vendorDir . '/classpreloader/classpreloader/src'),
//     'Backend\\' => array($baseDir . '/modules/backend'),
// );
}
