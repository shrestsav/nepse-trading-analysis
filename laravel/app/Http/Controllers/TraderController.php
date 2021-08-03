<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Stock;
use Carbon\CarbonPeriod;
use App\Models\PriceHistory;
use Laratrade\Trader\Facades\Trader;
use App\Http\Controllers\MeroLaganiController;
use App\Http\Controllers\NepseScrapingController;

class TraderController extends Controller
{
    private $buyStocks;

    private $fromDate;

    private $excludedStockTypes;

    public function __construct()
    {
        $this->buyStocks = [];

        $this->fromDate = '2020-01-01';

        $this->excludedStockTypes = config('system.ignore_stock_sector');
    }

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
                
                $ADX = Trader::adx($high, $low, $close, 14);
                $RSI = Trader::rsi($close, 14);
                $ADX_5 = Trader::adx($high, $low, $close, 5);

                $reverse_ADX = array_reverse($ADX);
                $reverse_ADX_5 = array_reverse($ADX_5);
                $reverse_RSI = array_reverse($RSI);

                $close_on_day = $close[count($close)-1];

                $ADX_today = $reverse_ADX[0];
                $ADX_yesterday = $reverse_ADX[1];
                
                $ADX_5_today = $reverse_ADX_5[0];
                $ADX_5_yesterday = $reverse_ADX_5[1];

                $RSI_today = $reverse_RSI[0];
                $RSI_yesterday = $reverse_RSI[1];

                if (
                    // $ADX_5_today > $ADX_5_yesterday &&
                    $ADX_today > $ADX_yesterday &&
                    $RSI_today > $RSI_yesterday &&
                    // ($RSI_today - $RSI_yesterday) > 4 &&
                    $ADX_today > 23 &&
                    $ADX_today < 30 &&
                    $RSI_today > 50 &&
                    $RSI_today < 60
                ) {
                    $buyRecommendations[] = [
                        'stock' => [
                            'id'           => $stock->id,
                            'company_name' => $stock->company_name,
                            'symbol'       => $stock->symbol,
                        ],
                        'close_on_day'     => $stock->priceHistory()->where('date', $tillDate)->first(),
                        'close_today'      => $stock->priceHistory()->first(),
                        'stop_loss'        => 0,
                        'reverse_RSI'      => $reverse_RSI,
                        'reverse_ADX'      => $reverse_ADX,
                    ];
                }
                else if ($ADX_today < $ADX_yesterday && $RSI_today < $RSI_yesterday){
                    $sellRecommendations[] = [
                        'stock' => [
                            'id'           => $stock->id,
                            'company_name' => $stock->company_name,
                            'symbol'       => $stock->symbol,
                        ],
                        'close_on_day'     => $close_on_day,
                        'stop_loss'        => 0,
                        'reverse_RSI'      => $reverse_RSI,
                        'reverse_ADX'      => $reverse_ADX,
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
            
            if (count($priceHistory) > 20 && !in_array($stock->sector->name,  $this->excludedStockTypes)) {
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
                    $ADX_today < 60 &&
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
                        'close_on_day'     => $stock->priceHistory()->where('date', $tillDate)->first(),
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
        $stock = Stock::with([
            'sector',
            'priceHistory' => function ($query) {
                $query->where('date', '>', $this->fromDate);
            }
        ])->where('symbol','CIT')->first();

        $priceHistory = $stock->priceHistory;
        
        $high = array_reverse($priceHistory->pluck('max_price')->toArray());
        $low = array_reverse($priceHistory->pluck('min_price')->toArray());
        $close = array_reverse($priceHistory->pluck('closing_price')->toArray());
        
        $MACD = Trader::macd($close, 12, 26, 9)[0];
        $adx = Trader::adx($high, $low, $close, 14);
        $rsi = Trader::rsi($close, 14);
        $EMA = Trader::ema($high, 10);

        return [
            'priceHistory' => count($priceHistory),
            'diff_EMA' => count($priceHistory) - count($EMA),
            'diff_MACD' => count($priceHistory) - count($MACD),
            'diff_ADX' => count($priceHistory) - count($adx),
            'diff_RSI' => count($priceHistory) - count($rsi),
            'ADX' => count($adx),
            'RSI' => count($rsi),
        ];
        
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

}
