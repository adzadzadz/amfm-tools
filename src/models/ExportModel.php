<?php

namespace adz\models;

use AdzWP\Model;

class ExportModel extends Model
{
    protected $table = 'exports';
    
    protected $fillable = [
        // Add fillable attributes here
    ];
    
    protected $guarded = [
        'id'
    ];
    
    // Add model relationships and methods here
}
