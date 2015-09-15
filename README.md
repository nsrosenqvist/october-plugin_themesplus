# Description

This plugin allows execution of complex code directly from plugins by providing
a service provider and composer support. Through the new service provider,
anything that can be done with a plugin can now be done with a theme.

It basically makes OctoberCMS load your theme as a plugin in the system. So
create the plugin definition file as you would normally but make sure to extend
`\NSRosenqvist\ThemesPlus\Classes\ThemesPlusBase` instead of the normal
`PluginBase`.

## Installation

Either install it via the marketplace or simply clone this repository into your
OctoberCMS installations' plugins directory.

```shell
cd <your-october-root>/plugins
git clone https://github.com/nsrosenqvist/october-plugin_themesplus.git nsrosenqvist/themesplus
```

Then run:
```shell
php artisan plugin:refresh NSRosenqvist.ThemesPlus`
```

## Usage

Make sure to specify `NSRosenqvist.ThemesPlus` as a theme dependency and then
add a *Plugin.php* file in the theme root with the following code:

```php
<?php namespace MyCompany\MyTheme;

class Plugin extends \NSRosenqvist\ThemesPlus\Classes\ThemesPlusBase
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

    // And all functions for things you want to register...
}
```
