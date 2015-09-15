<?php namespace NSRosenqvist\ThemesPlus\Models;

use Model;

class Settings extends Model
{
    public $implement = ['System.Behaviors.SettingsModel'];

    // A unique code
    public $settingsCode = 'nsrosenqvist_themesplus';

    // Reference to field configuration
    public $settingsFields = 'fields.yaml';
}
