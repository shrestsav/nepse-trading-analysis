<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use HasFactory;

    protected $fillable = ['sector_id', 'symbol', 'company_name'];
    
    /**
     * Get the Sector
     */
    public function sector()
    {
        return $this->belongsTo(Sector::class);
    }

    /**
     * Get the Price History Descending Order
     */
    public function priceHistory()
    {
        return $this->hasMany(PriceHistory::class)->orderBy('date','DESC');
    }

    /**
     * Get the Price History in Ascending Order
     */
    public function priceHistoryAsc()
    {
        return $this->hasMany(PriceHistory::class)->orderBy('date','ASC');
    }
}
