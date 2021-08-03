<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Stock;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Laratrade\Trader\Facades\Trader;
use App\Models\ScriptBackTesting;

class BackTestingController extends Controller
{
    private $excludedStockTypes;

    public function __construct()
    {
        $this->excludedStockTypes = config('system.ignore_stock_sector');
    }

    public function scriptsBackTesting($strategy)
    {
        ScriptBackTesting::truncate();
        
        $startDateStr = '2021-07-01';
        $tillDateStr = date('Y-m-d');
        $startDate = Carbon::createFromFormat('Y-m-d', $startDateStr);
        $priceHistoryFrom = Carbon::createFromFormat('Y-m-d', $startDateStr)->subDays(200);
        
        $stocks = Stock::with([
            'sector',
            'priceHistory' => function ($query) use ($priceHistoryFrom, $tillDateStr) {
                $query->where('date', '>=', $priceHistoryFrom)->where('date', '<=', $tillDateStr);
            }
        ])->get();
        // ->where('symbol', 'CIT')
        
        foreach ($stocks as $key => $stock) {
            $priceHistory = $stock->priceHistory;
            
            if (count($priceHistory) > 20 && !in_array($stock->sector->name,  $this->excludedStockTypes)) {

                switch ($strategy) {
                    case 'RSI-ADX':
                        $this->rsiAndAdxStrategy($stock, $startDate);
                      break;
                    case 'MA-EMA-ADX':
                        $this->maEmaAdxStrategy($stock, $startDate);
                      break;
                    default:
                      return "No Strategy Provided";
                  }
                
            }
        }

        return $this->calculateBackTestings();
    }

    public function rsiAndAdxStrategy($stock, $startDate)
    {
        $priceHistory = $stock->priceHistory;

        $high = array_reverse($priceHistory->pluck('max_price')->toArray());
        $low = array_reverse($priceHistory->pluck('min_price')->toArray());
        $close = array_reverse($priceHistory->pluck('closing_price')->toArray());

        $ADX = Trader::adx($high, $low, $close, 14);
        $RSI = Trader::rsi($close, 14);
        
        $reverse_ADX = array_reverse($ADX);
        $reverse_RSI = array_reverse($RSI);

        $stockTechnicalData = [];

        $index = 0;

        foreach($priceHistory as $history){
            // return $history;
            $historyDate = Carbon::createFromFormat('Y-m-d', $history['date']);

            if($historyDate->gt($startDate) || $historyDate->eq($startDate)){
                if(count($reverse_ADX) > $index+1){
                    $stockTechnicalData[] = [
                        'stock_id'       => $stock->id,
                        'symbol'         => $stock->symbol,
                        'date'           => $history['date'],
                        'closing_price'  => $history['closing_price'],
                        'change_percent' => $history['change_percent'],
                        'stop_loss'      => 0,
                        'indicators'     => [
                            'ADX_today'      => $reverse_ADX[$index],
                            'ADX_yesterday'  => $reverse_ADX[$index+1],
                            'RSI_today'      => $reverse_RSI[$index],
                            'RSI_yesterday'  => $reverse_RSI[$index+1]
                        ]  
                    ];
                }
            }
            else
                break;

            $index++;
        }
        
        $index = 0;

        $stockTechnicalDataReverse = array_reverse($stockTechnicalData);

        // return $stockTechnicalDataReverse;
        foreach($stockTechnicalDataReverse as $history){
            $ADX_today = $history['indicators']['ADX_today'];
            $ADX_yesterday = $history['indicators']['ADX_yesterday'];
            $RSI_today = $history['indicators']['RSI_today'];
            $RSI_yesterday = $history['indicators']['RSI_yesterday'];

            $this->buySellStoplossScripts('stop_loss', $history);
            
            if (
                $ADX_today > $ADX_yesterday &&
                $RSI_today > $RSI_yesterday &&
                // ($ADX_today - $ADX_yesterday) > 2 &&
                $ADX_today > 23 &&
                $ADX_today < 30 &&
                $RSI_today > 50 && 
                $RSI_today < 60
            ) {
                $this->buySellStoplossScripts('buy', $history);
            }
            elseif($ADX_today < $ADX_yesterday && ($ADX_yesterday - $ADX_today) > 2 && $RSI_today < $RSI_yesterday){
                $this->buySellStoplossScripts('sell', $history);
            }
            $index++;
        }
    }

    public function maEmaAdxStrategy($stock, $startDate)
    {
        $priceHistory = $stock->priceHistory;

        $high = array_reverse($priceHistory->pluck('max_price')->toArray());
        $low = array_reverse($priceHistory->pluck('min_price')->toArray());
        $close = array_reverse($priceHistory->pluck('closing_price')->toArray());
        $hlc3 = array_reverse($priceHistory->pluck('hlc3')->toArray());
        
        $ADX = Trader::adx($high, $low, $close, 5);
        $EMA_high = Trader::ema($high, 10);
        $EMA_low = Trader::ema($low, 10);
        $EMA_hlc3 = Trader::ema($hlc3, 10);
        
        $reverse_ADX = array_reverse($ADX);
        $reverse_EMA_high = array_reverse($EMA_high);
        $reverse_EMA_low = array_reverse($EMA_low);
        $reverse_EMA_hlc3 = array_reverse($EMA_hlc3);

        $stockTechnicalData = [];

        $index = 0;

        foreach($priceHistory as $history){
            $historyDate = Carbon::createFromFormat('Y-m-d', $history['date']);

            if($historyDate->gt($startDate) || $historyDate->eq($startDate)){
                if(count($reverse_ADX) > $index+1){
                    $stockTechnicalData[] = [
                        'date'           => $history['date'],
                        'stock_id'       => $stock->id,
                        'symbol'         => $stock->symbol,
                        'closing_price'  => $history['closing_price'],
                        'change_percent' => $history['change_percent'],
                        'stop_loss'      => $reverse_EMA_low[$index],
                        'indicators'     => [
                            'ADX_today'      => $reverse_ADX[$index],
                            'ADX_yesterday'  => $reverse_ADX[$index+1],
                            'EMA_high'       => $reverse_EMA_high[$index],
                            'EMA_low'        => $reverse_EMA_low[$index],
                            'EMA_hlc3'       => $reverse_EMA_hlc3[$index]
                        ]
                    ];
                }
            }
            else
                break;

            $index++;
        }
        
        $index = 0;

        $stockTechnicalDataReverse = array_reverse($stockTechnicalData);
        // return $stockTechnicalDataReverse;
        foreach($stockTechnicalDataReverse as $history){
            $close_today = $history['closing_price'];
            $change_today = $history['change_percent'];
            $ADX_today = $history['indicators']['ADX_today'];
            $ADX_yesterday = $history['indicators']['ADX_yesterday'];
            $EMA_high_today = $history['indicators']['EMA_high'];
            $EMA_low_today = $history['indicators']['EMA_low'];
            
            $this->buySellStoplossScripts('stop_loss', $history);
            
            if (
                $ADX_today > $ADX_yesterday &&
                ($ADX_today - $ADX_yesterday) > 5 &&
                $ADX_today > 40 &&
                $close_today > $EMA_high_today &&
                ((1-($EMA_high_today/$close_today)) > 0.05) &&
                $change_today > 0
            ) {
                $this->buySellStoplossScripts('buy', $history);
            }
            elseif($close_today < $EMA_low_today){
                $this->buySellStoplossScripts('sell', $history);
            }
            $index++;
        }
    }

    public function buySellStoplossScripts($type, $history)
    {
        if($type == 'buy'){
            $exists = ScriptBackTesting::where('stock_id', $history['stock_id'])->whereNull('sell_date')->exists();
    
            if(!$exists){
                $backTest = ScriptBackTesting::create([
                    'stock_id'   =>  $history['stock_id'],
                    'symbol'     =>  $history['symbol'],
                    'stop_loss'  =>  $history['stop_loss'],
                    'buy_date'   =>  $history['date'],
                    'buy_price'  =>  $history['closing_price'],
                    'indicators' =>  $history['indicators'],
                ]);
            }
        }
        else if($type == 'sell'){
            $backTest = ScriptBackTesting::where('stock_id', $history['stock_id'])->whereNull('sell_date');
    
            if($backTest->exists()){
                $buyDateParsed = Carbon::parse($backTest->first()->buy_date);
                $dateTodayParsed = Carbon::parse($history['date']);

                if($buyDateParsed->diffInDays($dateTodayParsed) >= 3){
                    $backTest->update([
                        'sell_date'  => $history['date'],
                        'sell_price' => $history['closing_price']
                    ]);
                }
            }
        }
        else if ($type == 'stop_loss'){
            $stop_loses = ScriptBackTesting::where('stock_id', $history['stock_id'])->whereNull('sell_date')->get();

            foreach($stop_loses as $s){
                if($history['closing_price'] <= $s->stop_loss){
                    $buyDateParsed = Carbon::parse($s->buy_date);
                    $dateTodayParsed = Carbon::parse($history['date']);

                    if($buyDateParsed->diffInDays($dateTodayParsed) >= 3){
                        $s->update([
                            'sell_date' => $history['date'],
                            'sell_price' => $history['closing_price']
                        ]);
                    }
                }
            }
        }
    }

    public function calculateBackTestings()
    {
        $all = scriptBackTesting::whereNull('sell_date')->get();

        foreach($all as $a){
            $stock = Stock::find($a->stock_id);
            $a->update([
                'sell_date'  => '2021-07-19',
                'sell_price' => $stock->priceHistory()->first()->closing_price
            ]);
        }

        $calculate = ScriptBackTesting::whereNotNull('sell_date')->get();
        
        $wins = 0;
        $loss = 0;
        $win_rate = 0;
        $loss_rate = 0;
        
        foreach($calculate as $c){
            if($c->buy_price >= $c->sell_price){
                $loss_rate = $loss_rate + (($c->sell_price - $c->buy_price) / $c->buy_price);
                $loss = $loss+1;
            }
            elseif($c->buy_price < $c->sell_price){
                $win_rate = $win_rate + (($c->sell_price - $c->buy_price) / $c->buy_price);
                $wins = $wins+1;
            }
        }

        return [
            'total_trades' =>  count($calculate),
            'wins'         =>  $wins,
            'profit_rate'  =>  $wins ? round(($win_rate/$wins)*100, 2) . " %" : 0,
            'loss'         =>  $loss,
            'loss_rate'    =>  $loss ? round(($loss_rate/$loss)*100, 2) . " %" : 0,
            'success_rate' =>  round(100*(($wins)/($wins + $loss)), 2) . " %",
        ];
    }

    public function scriptBackTesting()
    {
        $period = CarbonPeriod::create('2021-03-01', '2021-07-14');

        foreach ($period as $date){
            $date = $date->format('Y-m-d');
            $bestPicks = $this->getRecommendationsByRsiNAdx($date);
            $buyRecommendations = $bestPicks['buyRecommendations'];
            $sellRecommendations = $bestPicks['sellRecommendations'];
            
            foreach($buyRecommendations as $buy){
                $exists = ScriptBackTesting::where('stock_id', $buy['stock']['id'])->whereNull('sell_date')->exists();
                
                if(!$exists){
                    $backTest = ScriptBackTesting::create([
                        'stock_id'  => $buy['stock']['id'],
                        'symbol'    => $buy['stock']['symbol'],
                        'stop_loss' => $buy['stop_loss'],
                        'buy_date'  => $date,
                        'buy_price' => $buy['close_on_day']
                    ]);
                }
            }

            $stop_loses = ScriptBackTesting::whereNull('sell_date')->get();

            foreach($stop_loses as $s){
                $priceOnThatDay = PriceHistory::where('stock_id', $s->stock_id)->where('date', $date);
                
                if($priceOnThatDay->exists()){
                    $priceOnThatDay = $priceOnThatDay->first()->closing_price;

                    if($priceOnThatDay <= $s->stop_loss){
                        $s->update([
                            'sell_date' => $date,
                            'sell_price' => $priceOnThatDay
                        ]);
                    }
                }
            }

            foreach($sellRecommendations as $sell){
                $backTest = ScriptBackTesting::where('stock_id', $sell['stock']['id'])->whereNull('sell_date');
                
                if($backTest->exists()){
                    $backTest->update([
                        'sell_date' => $date,
                        'sell_price' => $sell['close_on_day']
                    ]);
                }
            }
        }
        
        $calculate = ScriptBackTesting::whereNotNull('sell_date')->get();
        
        $wins = 0;
        $loss = 0;
        
        foreach($calculate as $c){
            if($c->buy_price >= $c->sell_price){
                $loss = $loss+1;
            }
            elseif($c->buy_price < $c->sell_price)
                $wins = $wins+1;
        }
        return [
            'wins' => $wins,
            'loss' => $loss,
        ];
    }
}
