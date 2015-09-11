# Description

This plugin allows execution of complex code directly from plugins by providing
a service provider and composer support. Through the new service provider, 
anything that can be done with a plugin can now be done with a theme.

## Installation

* `git clone` to */plugins/nsrosenqvist/themesplug* directory
* `php artisan plugin:refresh Nsrosenqvist.ThemesPlus`
* In your active theme's directory add a *Theme.php* file with the following code:
```php
<?php namespace ThemesPlusTheme;

class Theme extends \System\Classes\PluginBase
{
    public function pluginDetails()
    {
        return [
            'name' => 'Themes+Theme',
            'description' => 'Execute complex tasks from your theme.',
            'author' => 'Your Name',
            'icon' => 'icon-leaf',
            'homepage' => 'https://www.yourite.com/'
        ];
    }

    public function boot()
    {
        // your code here
    }
}
```