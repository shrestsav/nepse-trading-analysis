<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScriptBackTesting extends Model
{
    use HasFactory;

    protected $fillable = ['stock_id', 'symbol', 'stop_loss', 'buy_date', 'buy_price', 'sell_date', 'sell_price', 'indicators'];

    protected $casts = [
        'indicators' => 'array',
    ];

    /**
     * Stock
     */
    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }
}
