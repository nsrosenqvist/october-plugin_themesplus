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
            'description' => 'Enables theme developers to use all functionality commonly found in plugins in a theme.',
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

                // Add theme to autoload through our definitions file
                ComposerManager::instance()->autoload($pluginPath);
            }
        }

        // dd(\Composer\Autoload\ClassLoader::findFile('MyCompany\\MyTheme\\Classes\\Radical'));
        // dd(ComposerManager::instance());
    }

    protected function makeDefinitionFile($namespace, $path)
    {
        $namespace = str_replace('\\', '\\\\', $namespace);

        // We can't simply create one definition entry because PSR-4 requires
        // the folder names to be uppercase and OctoberCMS' plugin directory structure
        // has them all lowercase, and we want it to be exactly like a Plugin.
        $themeDirs = [
            'components' => 'Components',
            'models' => 'Models',
            'classes' => 'Classes',
            'widgets' => 'Widgets',
            'formwidgets' => 'FormWidgets'
        ];

        $php = '<?php'.PHP_EOL;
        $php .= PHP_EOL;
        $php .= 'return array('.PHP_EOL;

        // Create a PSR-4 definition line for every theme subdirectory
        foreach ($themeDirs as $dirBaseName => $dirNamespace)
        {
            $php .= "\t".'\''.$namespace.'\\\\'.$dirNamespace.'\\\\\' => array(\''.$path.'/'.$dirBaseName.'\'),'.PHP_EOL;
        }

        $php .= ');'.PHP_EOL;
        return $php;
    }
}
