<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Stock;
use Carbon\CarbonPeriod;
use App\Models\PriceHistory;
use App\Models\ScriptBackTesting;
use Laratrade\Trader\Facades\Trader;
use App\Http\Controllers\MeroLaganiController;
use App\Http\Controllers\NepseScrapingController;

class TraderController extends Controller
{
    private $buyStocks = [];

    private $fromDate = '2020-01-01';

    private $excludedStockTypes = ['Mutual Fund', 'Promotor Share', 'Preferred Stock', 'Corporate Debenture'];

    public function getRecommendationsByRsiNAdx($tillDate)
    {
        $buyRecommendations = [];
        $sellRecommendations = [];

        $stocks = Stock::with([
            'sector',
            'priceHistory' => function ($query) use ($tillDate) {
                $query->where('date', '>', $this->fromDate)->where('date', '<=', $tillDate);
            }
        ])->get();
        
        foreach ($stocks as $key => $stock) {
            $priceHistory = $stock->priceHistory;
            
            if (count($priceHistory) > 50 && !in_array($stock->sector->name,  $this->excludedStockTypes)) {
                $high = array_reverse($priceHistory->pluck('max_price')->toArray());
                $low = array_reverse($priceHistory->pluck('min_price')->toArray());
                $close = array_reverse($priceHistory->pluck('closing_price')->toArray());
                
                $adx = Trader::adx($high, $low, $close, 14);
                $rsi = Trader::rsi($close, 14);

                $reverse_adx = array_reverse($adx);
                $reverse_rsi = array_reverse($rsi);

                $close_on_day = $close[count($close)-1];

                $adx_today = $reverse_adx[0];
                $adx_yesterday = $reverse_adx[1];

                $rsi_today = $reverse_rsi[0];
                $rsi_yesterday = $reverse_rsi[1];

                if (
                    $adx_today > $adx_yesterday &&
                    $adx_today > 23 &&
                    $rsi_today > $rsi_yesterday &&
                    $rsi_today > 50
                ) {
                    $buyRecommendations[] = [
                        'stock' => [
                            'id'           => $stock->id,
                            'company_name' => $stock->company_name,
                            'symbol'       => $stock->symbol,
                        ],
                        'close_on_day'     => $close_on_day,
                        'stop_loss'        => 0,
                        'reverse_RSI'      => $reverse_rsi,
                        'reverse_ADX'      => $reverse_adx,
                    ];
                }
                elseif($adx_today < $adx_yesterday && $rsi_today < $rsi_yesterday){
                    $sellRecommendations[] = [
                        'stock' => [
                            'id'           => $stock->id,
                            'company_name' => $stock->company_name,
                            'symbol'       => $stock->symbol,
                        ],
                        'close_on_day'     => $close_on_day,
                        'stop_loss'        => 0,
                        'reverse_RSI'      => $reverse_rsi,
                        'reverse_ADX'      => $reverse_adx,
                    ];
                }
            }
        }

        $result = [
            'buyRecommendations'  => $buyRecommendations,
            'sellRecommendations' => $sellRecommendations
        ];

        return $result;
    }

    public function getRecommendationsByRsiNMacd($tillDate)
    {
        $recommendations = [];

        $stocks = Stock::with([
            'sector',
            'priceHistory' => function ($query) use ($tillDate) {
                $query->where('date', '>', $this->fromDate)->where('date', '<=', $tillDate);
            }
        ])->get();

        foreach ($stocks as $key => $stock) {
            $priceHistory = $stock->priceHistory;

            if (count($priceHistory) > 50 && !in_array($stock->sector->name,  $this->excludedStockTypes)) {
                $high = array_reverse($priceHistory->pluck('max_price')->toArray());
                $low = array_reverse($priceHistory->pluck('min_price')->toArray());
                $close = array_reverse($priceHistory->pluck('closing_price')->toArray());

                $MACD = Trader::macd($close, 12, 26, 9)[0];
                $RSI = Trader::rsi($close, 14);

                $reverse_MACD = array_reverse($MACD);
                $reverse_RSI = array_reverse($RSI);

                $MACD_today = $reverse_MACD[0];
                $MACD_yesterday = $reverse_MACD[1];

                $RSI_today = $reverse_RSI[0];
                $RSI_yesterday = $reverse_RSI[1];

                if (
                    $MACD_today > $MACD_yesterday &&
                    $RSI_today > $RSI_yesterday &&
                    $RSI_today > 50
                ) {
                    $recommendations[$stock->symbol] = [
                        'stock' => [
                            'company_name' => $stock->company_name,
                            'symbol' => $stock->symbol,
                        ],
                        'reverse_RSI' => $reverse_RSI,
                        'reverse_MACD' => $reverse_MACD,
                    ];
                }
            }
        }

        return response()->json($recommendations);
    }

    public function getRecommendationsByMaEmaAdx($tillDate)
    {
        //test
        // $merolagani = new MeroLaganiController;
        // $merolagani->livePrice();
        
        $buyRecommendations = [];
        $sellRecommendations = [];

        $stocks = Stock::with([
            'sector',
            'priceHistory' => function ($query) use ($tillDate) {
                $query->where('date', '>', $this->fromDate)->where('date', '<=', $tillDate);
            }
        ])->get();

        foreach ($stocks as $key => $stock) {
            $priceHistory = $stock->priceHistory;
            
            if (count($priceHistory) > 50 && !in_array($stock->sector->name,  $this->excludedStockTypes)) {
                $tillDate = $priceHistory->pluck('date')->toArray()[0];
                $change = $priceHistory->pluck('change_percent')->toArray();

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
                
                $close_on_day = $close[count($close)-1];
                $change_today = $change[0];
                
                $ADX_today = $reverse_ADX[0];
                $ADX_yesterday = $reverse_ADX[1];

                $EMA_high_today = $reverse_EMA_high[0];
                $EMA_high_yesterday = $reverse_EMA_high[1];
                
                $EMA_low_today = $reverse_EMA_low[0];
                $EMA_low_yesterday = $reverse_EMA_low[1];

                $EMA_hlc3_today = $reverse_EMA_hlc3[0];
                $EMA_hlc3_yesterday = $reverse_EMA_hlc3[1];
                
                if (
                    // $ADX_today > $ADX_yesterday &&
                    // ($ADX_today - $ADX_yesterday) > 5 &&
                    $ADX_today > 40 &&
                    $close_on_day > $EMA_high_today &&
                    // ((1-($EMA_high_today/$close_on_day)) > 0.05) &&
                    $change_today > 0
                ) {
                    $buyRecommendations[] = [
                        'stock' => [
                            'id'           => $stock->id,
                            'company_name' => $stock->company_name,
                            'symbol'       => $stock->symbol,
                        ],
                        'stop_loss'        => $EMA_low_today,
                        'close_on_day'     => $close_on_day,
                        'close_today'      => $stock->priceHistory()->first(),
                        'tillDate'         => $tillDate,
                        'reverse_ADX'      => $reverse_ADX,
                        'reverse_EMA_high' => $reverse_EMA_high,
                        'reverse_EMA_hlc3' => $reverse_EMA_hlc3,
                        'reverse_EMA_low'  => $reverse_EMA_low,
                        'adx_diff'         => $ADX_today-$ADX_yesterday
                    ];
                }
                elseif($close_on_day < $EMA_low_today){
                    $sellRecommendations[] = [
                        'stock' => [
                            'id'           => $stock->id,
                            'company_name' => $stock->company_name,
                            'symbol'       => $stock->symbol,
                        ],
                        'close_on_day'     => $close_on_day,
                        'close_today'      => $stock->priceHistory()->first(),
                        'tillDate'         => $tillDate,
                        'reverse_ADX'      => $reverse_ADX,
                        'reverse_EMA_high' => $reverse_EMA_high,
                        'reverse_EMA_hlc3' => $reverse_EMA_hlc3,
                        'reverse_EMA_low'  => $reverse_EMA_low,
                        'adx_diff'         => $ADX_today-$ADX_yesterday
                    ];
                }
            }
        }

        // $result = [
        //     'buyRecommendations'  => $buyRecommendations,
        //     'sellRecommendations' => $sellRecommendations
        // ];

        return response()->json($buyRecommendations);
        // return $result;
    }

    public function tenPeriosRSIBelowThirtyStrategy()
    {
        $this->buyStocks = [];

        $stocks = Stock::with([
            'sector',
            'priceHistory' => function ($query) {
                $query->where('date', '>', $this->fromDate)->where('date', '<=', '2021-07-12');
            }
        ])->get();
        
        foreach ($stocks as $key => $stock) {
            $priceHistory = $stock->priceHistory;
            
            if (count($priceHistory) > 50 && !in_array($stock->sector->name,  $this->excludedStockTypes)) {
                $high = array_reverse($priceHistory->pluck('max_price')->toArray());
                $low = array_reverse($priceHistory->pluck('min_price')->toArray());
                $close = array_reverse($priceHistory->pluck('closing_price')->toArray());

                // $adx = Trader::adx($high, $low, $close, 14);
                $rsi = Trader::rsi($close, 14);

                // $reverse_adx = array_reverse($adx);
                $reverse_rsi = array_reverse($rsi);

                // $adx_today = $reverse_adx[0];
                // $adx_yesterday = $reverse_adx[1];

                $rsi_today = $reverse_rsi[0];
                $rsi_yesterday = $reverse_rsi[1];

                if ($rsi_today <= 30) {
                    $this->buyStocks[$stock->symbol] = [
                        'stock' => [
                            'company_name' => $stock->company_name,
                            'symbol' => $stock->symbol,
                        ],
                        'close_today' => $stock->priceHistory,
                        'reverse_RSI' => $reverse_rsi,
                    ];
                }
            }
        }

        return response()->json($this->buyStocks);
    }

    public function test()
    {
        $nepalStock = new NepseScrapingController();
        $history = $nepalStock->priceHistory('NLG');
        // $prices = [];

        // foreach($history as $price){
        //     $prices[] = $price[2];
        // }
        $report = [];
        $forRSI = [];
        
        $priceHistory = Stock::where('symbol', 'NLG')->first()->priceHistory;
        // $real = $priceHistory->pluck('closing_price')->toArray();
        // $rsi = Trader::rsi($real, 14);
        
        
        for($i = 0; $i < 200; $i++){
            if($history[$i][2] != $priceHistory[$i]['closing_price']){
                $report[] = [
                    'dateNepaliPaisa' => $priceHistory[$i]['date'],
                    'priceNepaliPaisa' => $priceHistory[$i]['closing_price'],
                    'dateNepalStock' => $history[$i][1],
                    'priceNepalStock' => $history[$i][2]
                ];
            }
            else
                $forRSI[] = $priceHistory[$i]['closing_price'];
        }
        
        return count($report) > 0 ? $report : Trader::rsi(array_reverse($forRSI), 14);

        // $result = Trader::trima($real, 30);

        // $result = Trader::bbands($real, 20, 2.0, 2.0, 0);

        // $highBand = array_reverse($result[0]);
        // $midBand = array_reverse($result[1]);
        // $lowBand = array_reverse($result[2]);

        // $result = [
        //     [
        //         $highBand[0], $midBand[0], $lowBand[0]
        //     ],
        //     [
        //         $highBand[1], $midBand[1], $lowBand[1]
        //     ],
        //     [
        //         $highBand[2], $midBand[2], $lowBand[2]
        //     ],
        // ];
        // return $result;

        
    }

    public function test1()
    {
        $nepalStock = new NepseScrapingController();
        $history = $nepalStock->priceHistory('NLG');
        // $prices = [];

        // foreach($history as $price){
        //     $prices[] = $price[2];
        // }
        $report = [];
        $forRSI = [];
        
        $priceHistory = Stock::where('symbol', 'NLG')->first()->priceHistory;
        // $real = $priceHistory->pluck('closing_price')->toArray();
        // $rsi = Trader::rsi($real, 14);
        
        
        for($i = 0; $i < 200; $i++){
            if($history[$i][2] != $priceHistory[$i]['closing_price']){
                $report[] = [
                    'dateNepaliPaisa' => $priceHistory[$i]['date'],
                    'priceNepaliPaisa' => $priceHistory[$i]['closing_price'],
                    'dateNepalStock' => $history[$i][1],
                    'priceNepalStock' => $history[$i][2]
                ];
            }
            else
                $forRSI[] = $priceHistory[$i]['closing_price'];
        }
        
        return count($report) > 0 ? $report : Trader::rsi(array_reverse($forRSI), 14);

        // $result = Trader::trima($real, 30);

        // $result = Trader::bbands($real, 20, 2.0, 2.0, 0);

        // $highBand = array_reverse($result[0]);
        // $midBand = array_reverse($result[1]);
        // $lowBand = array_reverse($result[2]);

        // $result = [
        //     [
        //         $highBand[0], $midBand[0], $lowBand[0]
        //     ],
        //     [
        //         $highBand[1], $midBand[1], $lowBand[1]
        //     ],
        //     [
        //         $highBand[2], $midBand[2], $lowBand[2]
        //     ],
        // ];
        // return $result;

        
    }


    public function backTestsRSINADX()
    {
        ScriptBackTesting::truncate();
        
        $startDate = Carbon::createFromFormat('Y-m-d', '2019-01-01');
        $tillDate = '2021-07-14';

        $stocks = Stock::with([
            'sector',
            'priceHistory' => function ($query) use ($tillDate) {
                $query->where('date', '>', $this->fromDate)->where('date', '<=', $tillDate);
            }
        ])->get();
        // ->where('symbol', 'CIT')
        foreach ($stocks as $key => $stock) {
            $priceHistory = $stock->priceHistory;
            
            if (count($priceHistory) > 50 && !in_array($stock->sector->name,  $this->excludedStockTypes)) {
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
                    $historyDate = Carbon::createFromFormat('Y-m-d', $history['date']);

                    if($historyDate->gt($startDate) || $historyDate->eq($startDate)){
                        if(count($reverse_ADX) > $index+1){
                            $stockTechnicalData[] = [
                                'date'           => $history['date'],
                                'closing_price'  => $history['closing_price'],
                                'change_percent' => $history['change_percent'],
                                'ADX_today'      => $reverse_ADX[$index],
                                'ADX_yesterday'  => $reverse_ADX[$index+1],
                                'RSI_today'      => $reverse_RSI[$index],
                                'RSI_yesterday'  => $reverse_RSI[$index+1]
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
                    $date_today = $history['date'];
                    $ADX_today = $history['ADX_today'];
                    $ADX_yesterday = $history['ADX_yesterday'];
                    $close_today = $history['closing_price'];
                    $RSI_today = $history['RSI_today'];
                    $RSI_yesterday = $history['RSI_yesterday'];
                    $change_today = $history['change_percent'];

                    $stop_loses = ScriptBackTesting::where('stock_id', $stock->id)->whereNull('sell_date')->get();

                    foreach($stop_loses as $s){
                        if($close_today <= $s->stop_loss){
                            $buyDateParsed = Carbon::parse($s->buy_date);
                            $dateTodayParsed = Carbon::parse($date_today);

                            if($buyDateParsed->diffInDays($dateTodayParsed) >= 3){
                                $s->update([
                                    'sell_date' => $date_today,
                                    'sell_price' => $close_today
                                ]);
                            }
                        }
                    }
                    
                    if (
                        $ADX_today > $ADX_yesterday &&
                        $ADX_today > 23 &&
                        $RSI_today > $RSI_yesterday &&
                        $RSI_today > 50
                    ) {
                        // return $history;
                        $exists = ScriptBackTesting::where('stock_id', $stock->id)->whereNull('sell_date')->exists();
            
                        if(!$exists){
                            $backTest = ScriptBackTesting::create([
                                'stock_id'   => $stock->id,
                                'symbol'     => $stock->symbol,
                                'stop_loss'  => 0,
                                'buy_date'   => $date_today,
                                'buy_price'  => $close_today,
                                'indicators' => [
                                    'adx'    => $ADX_today,
                                    'close'  => $close_today,
                                ]
                            ]);
                        }
                        
                    }
                    elseif($ADX_today < $ADX_yesterday && $RSI_today < $RSI_yesterday){
                    // elseif($ADX_today < $ADX_yesterday){
                        $backTest = ScriptBackTesting::where('stock_id', $stock->id)->whereNull('sell_date');
            
                        if($backTest->exists()){
                            $buyDateParsed = Carbon::parse($backTest->first()->buy_date);
                            $dateTodayParsed = Carbon::parse($date_today);

                            if($buyDateParsed->diffInDays($dateTodayParsed) >= 3){
                                $backTest->update([
                                    'sell_date' => $date_today,
                                    'sell_price' => $close_today
                                ]);
                            }
                        }
                    }
                    $index++;
                }
            }
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
            'wins'      =>  $wins,
            'win_rate'  =>  round(($win_rate/$wins)*100, 2) . " %",
            'loss'      =>  $loss,
            'loss_rate' =>  round(($loss_rate/$loss)*100, 2) . " %",
        ];
    }

    public function backTests()
    {
        ScriptBackTesting::truncate();
        
        $startDate = Carbon::createFromFormat('Y-m-d', '2019-01-01');
        $tillDate = '2021-07-14';

        $stocks = Stock::with([
            'sector',
            'priceHistory' => function ($query) use ($tillDate) {
                $query->where('date', '>', $this->fromDate)->where('date', '<=', $tillDate);
            }
        ])->where('symbol', 'NLICL')->get();
        // ->where('symbol', 'CIT')
        foreach ($stocks as $key => $stock) {
            $priceHistory = $stock->priceHistory;
            
            if (count($priceHistory) > 50 && !in_array($stock->sector->name,  $this->excludedStockTypes)) {
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
                                'closing_price'  => $history['closing_price'],
                                'change_percent' => $history['change_percent'],
                                'ADX_today'      => $reverse_ADX[$index],
                                'ADX_yesterday'  => $reverse_ADX[$index+1],
                                'EMA_high'       => $reverse_EMA_high[$index],
                                'EMA_low'        => $reverse_EMA_low[$index],
                                'EMA_hlc3'       => $reverse_EMA_hlc3[$index]
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
                    $date_today = $history['date'];
                    $ADX_today = $history['ADX_today'];
                    $ADX_yesterday = $history['ADX_yesterday'];
                    $close_today = $history['closing_price'];
                    $EMA_high_today = $history['EMA_high'];
                    $EMA_low_today = $history['EMA_low'];
                    $change_today = $history['change_percent'];

                    $stop_loses = ScriptBackTesting::where('stock_id', $stock->id)->whereNull('sell_date')->get();

                    foreach($stop_loses as $s){
                        if($close_today <= $s->stop_loss){
                            $buyDateParsed = Carbon::parse($s->buy_date);
                            $dateTodayParsed = Carbon::parse($date_today);

                            if($buyDateParsed->diffInDays($dateTodayParsed) >= 3){
                                $s->update([
                                    'sell_date' => $date_today,
                                    'sell_price' => $close_today
                                ]);
                            }
                        }
                    }
                    
                    if (
                        // $ADX_today > $ADX_yesterday &&
                        // ($ADX_today - $ADX_yesterday) > 5 &&
                        $ADX_today > 40 &&
                        $close_today > $EMA_high_today &&
                        // ((1-($EMA_high_today/$close_today)) > 0.05) &&
                        $change_today > 0
                    ) {
                        // return $history;
                        $exists = ScriptBackTesting::where('stock_id', $stock->id)->whereNull('sell_date')->exists();
            
                        if(!$exists){
                            $backTest = ScriptBackTesting::create([
                                'stock_id'   => $stock->id,
                                'symbol'     => $stock->symbol,
                                'stop_loss'  => $EMA_low_today,
                                'buy_date'   => $date_today,
                                'buy_price'  => $close_today,
                                'indicators' => [
                                    'adx'      => $ADX_today,
                                    'EMA_high' => $EMA_high_today,
                                    'close'    => $close_today,
                                ]
                            ]);
                        }
                        
                    }
                    elseif($close_today < $EMA_low_today){
                    // elseif($ADX_today < $ADX_yesterday){
                        $backTest = ScriptBackTesting::where('stock_id', $stock->id)->whereNull('sell_date');
            
                        if($backTest->exists()){
                            $buyDateParsed = Carbon::parse($backTest->first()->buy_date);
                            $dateTodayParsed = Carbon::parse($date_today);

                            if($buyDateParsed->diffInDays($dateTodayParsed) >= 3){
                                $backTest->update([
                                    'sell_date' => $date_today,
                                    'sell_price' => $close_today
                                ]);
                            }
                        }
                    }
                    $index++;
                }
            }
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
            'wins'      =>  $wins,
            'win_rate'  =>  round(($win_rate/$wins)*100, 2) . " %",
            'loss'      =>  $loss,
            'loss_rate' =>  round(($loss_rate/$loss)*100, 2) . " %",
        ];
    }

    public function buySellStoploss($type, $stocks = [])
    {
        // date ghumai rakhnu pardaina aba
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
