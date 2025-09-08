<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AssetLocation extends Model
{
    // Explicitly set the table to avoid Eloquent defaulting to 'locations'
    protected $table = 'asset_locations';
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name'
    ];

    public function subLocations()
    {
        return $this->hasMany(SubLocation::class);
    }

    public function assets()
    {
        return $this->hasMany(Asset::class, 'asset_location_id');
    }
}
