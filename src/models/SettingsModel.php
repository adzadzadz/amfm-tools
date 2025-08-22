<?php

namespace adz\models;

use AdzWP\Model;

class SettingsModel extends Model
{
    protected $table = 'settingses';
    
    protected $fillable = [
        // Add fillable attributes here
    ];
    
    protected $guarded = [
        'id'
    ];
    
    // Add model relationships and methods here
}
