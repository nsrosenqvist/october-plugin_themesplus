<?php namespace Nsrosenqvist\ThemesPlus\Classes;

use Cms\Classes\Theme;
use Config;
use File;
use App;
use Event;
use Str;
use System\Models\MailTemplate;
use System\Classes\PluginManager;
use System\Classes\MarkupManager;
use System\Classes\SettingsManager;
use Cms\Classes\ComponentManager;
use Backend\Classes\NavigationManager;
use Backend\Classes\AuthManager;
use Backend\Classes\WidgetManager;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class ThemePluginManager {
    private $registered = false;
    private $booted = false;
    private $plugins = [];

    public function init()
    {
        // Register and boot the plugins
        $this->loadPlugins();
        $this->loadDependencies();
        $this->registerAll();
        $this->bootAll();

        // Run remaining registrations
        $this->registerMarkupTags();
        $this->registerComponents();
        $this->registerNavigation();
        $this->registerPermissions();
        $this->registerSettings();
        $this->registerFormWidgets();
        $this->registerReportWidgets();
        // $this->registerMailTemplates();
        $this->registerSchedule();
    }

    private function registerMarkupTags()
    {
        foreach ($this->plugins as $id => $plugin) {
            $items = $plugin->registerMarkupTags();
            if (!is_array($items)) {
                continue;
            }

            foreach ($items as $type => $definitions) {
                if (!is_array($definitions)) {
                    continue;
                }

                MarkupManager::instance()->registerExtensions($type, $definitions);
            }

        }
    }

    private function registerComponents()
    {
        foreach ($this->plugins as $plugin) {
            $components = $plugin->registerComponents();
            if (!is_array($components)) {
                continue;
            }

            foreach ($components as $className => $code) {
                ComponentManager::instance()->registerComponent($className, $code, $plugin);
            }
        }
    }

    private function registerNavigation()
    {
        foreach ($this->plugins as $id => $plugin) {
            $items = $plugin->registerNavigation();
            if (!is_array($items)) {
                continue;
            }

            NavigationManager::instance()->registerMenuItems($id, $items);
        }
    }

    private function registerPermissions()
    {
        foreach ($this->plugins as $id => $plugin) {
            $items = $plugin->registerPermissions();
            if (!is_array($items)) {
                continue;
            }

            AuthManager::instance()->registerPermissions($id, $items);
        }
    }

    private function registerSettings()
    {
        foreach ($this->plugins as $id => $plugin) {
            $items = $plugin->registerSettings();
            if (!is_array($items)) {
                continue;
            }

            SettingsManager::instance()->registerSettingItems($id, $items);
        }
    }

    private function registerFormWidgets()
    {
        foreach ($this->plugins as $plugin) {
            if (!is_array($widgets = $plugin->registerFormWidgets())) {
                continue;
            }

            foreach ($widgets as $className => $widgetInfo) {
                WidgetManager::instance()->registerFormWidget($className, $widgetInfo);
            }
        }
    }

    private function registerReportWidgets()
    {
        foreach ($this->plugins as $plugin) {
            if (!is_array($widgets = $plugin->registerReportWidgets())) {
                continue;
            }

            foreach ($widgets as $className => $widgetInfo) {
                WidgetManager::instance()->registerReportWidget($className, $widgetInfo);
            }
        }
    }

    // private function registerMailTemplates()
    // {
    //     foreach ($this->plugins as $pluginId => $pluginObj) {
    //         $templates = $pluginObj->registerMailTemplates();
    //         if (!is_array($templates)) {
    //             continue;
    //         }
    //
    //         MailTemplate::instance()->registerMailTemplates($templates);
    //     }
    // }

    private function registerSchedule()
    {
        $plugins = $this->plugins;

        Event::listen('console.schedule', function($schedule) use ($plugins) {
            foreach ($plugins as $plugin) {
                if (method_exists($plugin, 'registerSchedule')) {
                    $plugin->registerSchedule($schedule);
                }
            }
        });
    }

    private function registerAll()
    {
        if ($this->registered) {
            return;
        }

        foreach ($this->plugins as $pluginId => $plugin) {
            PluginManager::instance()->registerPlugin($plugin, $pluginId);
        }

        $this->registered = true;
    }

    private function bootAll()
    {
        if ($this->booted) {
            return;
        }

        foreach ($this->plugins as $plugin) {
            PluginManager::instance()->bootPlugin($plugin);
        }

        $this->booted = true;
    }

    private function loadPlugins()
    {
        /**
         * Load the plugins and register them into our own plugin array
         */
        foreach ($this->getPluginNamespaces() as $namespace => $path) {
            $classObj = PluginManager::instance()->loadPlugin($namespace, $path);
            $classId = PluginManager::instance()->getIdentifier($classObj);
            $this->plugins[$classId] = $classObj;
        }

        return $this->plugins;
    }

    /**
     * Returns a flat array of vendor plugin namespaces and their paths
     */
    // From october/modules/system/classes/PluginManager.php:336
    private function getPluginNamespaces()
    {
        $classNames = [];

        foreach ($this->getVendorAndPluginNames() as $vendorName => $vendorList) {
            foreach ($vendorList as $pluginName => $pluginPath) {
                $namespace = '\\'.$vendorName.'\\'.$pluginName;
                $namespace = Str::normalizeClassName($namespace);
                $classNames[$namespace] = $pluginPath;
            }
        }

        return $classNames;
    }

    /**
     * Returns a 2 dimensional array of vendors and their plugins.
     */
    // From october/modules/system/classes/PluginManager.php:354
    private function getVendorAndPluginNames()
    {
        $plugins = [];

        $dirPath = $this->getPluginsPath();
        if (!File::isDirectory($dirPath)) {
            return $plugins;
        }

        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dirPath));
        $it->setMaxDepth(2);
        $it->rewind();

        while ($it->valid()) {
            if (($it->getDepth() > 1) && $it->isFile() && (strtolower($it->getFilename()) == "plugin.php")) {
                $filePath = dirname($it->getPathname());
                $pluginName = basename($filePath);
                $vendorName = basename(dirname($filePath));
                $plugins[$vendorName][$pluginName] = $filePath;
            }

            $it->next();
        }

        return $plugins;
    }

    private function loadDependencies()
    {
        foreach ($this->plugins as $id => $plugin) {
            if (!$required = PluginManager::instance()->getDependencies($plugin)) {
                continue;
            }

            $disable = false;
            foreach ($required as $require) {
                if (!PluginManager::instance()->hasPlugin($require)) {
                    $disable = true;
                }
                elseif (($pluginObj = PluginManager::instance()->findByIdentifier($require)) && $pluginObj->disabled) {
                    $disable = true;
                }
            }

            if ($disable) {
                PluginManager::instance()->disablePlugin($id);
            }
            else {
                PluginManager::instance()->enablePlugin($id);
            }
        }
    }

    private function getPluginsPath()
    {
        return $this->getThemePath().'/plugins';
    }

    private function getThemePath()
    {
        $theme = Theme::getActiveTheme();
        $themeDir = themes_path().'/'.$theme->getDirName();
        return $themeDir;
    }
}
