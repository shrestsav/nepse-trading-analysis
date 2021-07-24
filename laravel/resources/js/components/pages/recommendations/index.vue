<template>
  <v-container>
    <v-row v-bind:class="{'mb-2' : !section.MA_EMA_ADX.display}">
      <v-app-bar dense>
        <v-toolbar-title>MA - EMA - ADX</v-toolbar-title>

        <v-spacer></v-spacer>

        <v-btn v-if="section.MA_EMA_ADX.loaded" icon @click="section.MA_EMA_ADX.display = !section.MA_EMA_ADX.display">
          <v-icon v-if="section.MA_EMA_ADX.display">mdi-unfold-less-horizontal</v-icon>
          <v-icon v-else>mdi-unfold-more-horizontal</v-icon>
        </v-btn>

        <v-col v-else cols="10">
          <v-progress-linear color="deep-purple accent-4" indeterminate rounded height="6"></v-progress-linear>
        </v-col>
      </v-app-bar>
    </v-row>

    <v-slide-y-reverse-transition>
      <MA-EMA-ADX-List v-show="section.MA_EMA_ADX.display" :by_MA_EMA_ADX="by_MA_EMA_ADX" :sparkline="sparkline"></MA-EMA-ADX-List>
    </v-slide-y-reverse-transition>

    <v-row v-bind:class="{'mb-2' : !section.RSI_ADX.display}">
      <v-app-bar dense>
        <v-toolbar-title>RSI - ADX</v-toolbar-title>

        <v-spacer></v-spacer>

        <v-btn v-if="section.RSI_ADX.loaded" icon @click="section.RSI_ADX.display = !section.RSI_ADX.display">
          <v-icon v-if="section.RSI_ADX.display">mdi-unfold-less-horizontal</v-icon>
          <v-icon v-else>mdi-unfold-more-horizontal</v-icon>
        </v-btn>

        <v-col v-else cols="10">
          <v-progress-linear color="deep-purple accent-4" indeterminate rounded height="6"></v-progress-linear>
        </v-col>
      </v-app-bar>
    </v-row>

    <v-slide-y-reverse-transition>
      <RSI-ADX-List v-if="recommendationView == 'list' && section.RSI_ADX.display" :by_RSI_ADX="by_RSI_ADX" :sparkline="sparkline"></RSI-ADX-List>

      <RSI-ADX-Tile v-if="recommendationView == 'tile' && section.RSI_ADX.display" :by_RSI_ADX="by_RSI_ADX" :sparkline="sparkline"></RSI-ADX-Tile>
    </v-slide-y-reverse-transition>

    <v-row>
      <v-app-bar dense>
        <v-toolbar-title>RSI - MACD</v-toolbar-title>

        <v-spacer></v-spacer>

        <v-btn v-if="section.RSI_MACD.loaded" icon @click="section.RSI_MACD.display = !section.RSI_MACD.display">
          <v-icon v-if="section.RSI_MACD.display">mdi-unfold-less-horizontal</v-icon>
          <v-icon v-else>mdi-unfold-more-horizontal</v-icon>
        </v-btn>

        <v-col v-else cols="10">
          <v-progress-linear color="deep-purple accent-4" indeterminate rounded height="6"></v-progress-linear>
        </v-col>
      </v-app-bar>
    </v-row>

    <v-slide-y-reverse-transition>
      <RSI-MACD-List v-if="recommendationView == 'list' && section.RSI_MACD.display" :by_RSI_MACD="by_RSI_MACD" :sparkline="sparkline"></RSI-MACD-List>

      <RSI-MACD-Tile v-if="recommendationView == 'tile' && section.RSI_MACD.display" :by_RSI_MACD="by_RSI_MACD" :sparkline="sparkline"></RSI-MACD-Tile>
    </v-slide-y-reverse-transition>
  </v-container>
</template>

<script>
const gradients = [
  ["#222"],
  ["#42b3f4"],
  ["red", "orange", "yellow"],
  ["purple", "violet"],
  ["#00c6ff", "#F0F", "#FF0"],
  ["#f72047", "#ffd200", "#1feaea"]
];
import RSIADXList from "./components/RSI-ADX-List";
import RSIADXTile from "./components/RSI-ADX-Tile";
import RSIMACDList from "./components/RSI-MACD-List";
import RSIMACDTile from "./components/RSI-MACD-Tile";
import MAEMAADXList from "./components/MA-EMA-ADX-List";

export default {
  components: {
    RSIADXList,
    RSIADXTile,
    RSIMACDList,
    RSIMACDTile,
    MAEMAADXList
  },
  data() {
    return {
      sparkline: {
        width: 2,
        radius: 10,
        padding: 8,
        lineCap: "round",
        gradient: gradients[5],
        gradientDirection: "top",
        gradients,
        fill: false,
        type: "trend",
        autoLineWidth: false
      },
      loaded: false,
      by_RSI_ADX: {},
      by_RSI_MACD: {},
      by_MA_EMA_ADX: [],
      section: {
        RSI_ADX: {
          display: true,
          loaded: false
        },
        RSI_MACD: {
          display: false,
          loaded: false
        },
        MA_EMA_ADX: {
          display: false,
          loaded: false
        }
      }
    };
  },
  created() {},
  mounted() {
    this.initialize();

    let dateTime = new Date();
    let hour = dateTime.getHours();

    if (hour >= 11 && hour <= 14)
      this.$store.commit("changeIsLiveMarket", true);
  },
  methods: {
    initialize() {
      this.getRecommendationsByMaEmaAdx();
      this.getRecommendationsByRsiNAdx();
      this.getRecommendationsByRsiNMacd();
    },
    getRecommendationsByMaEmaAdx() {
      this.by_MA_EMA_ADX = [];
      this.section.MA_EMA_ADX.loaded = false;

      axios
        .get("/api/get_recommendations_by_ma_ema_adx/" + this.forDateof)
        .then(response => {
          let recommendations = response.data;
          recommendations.forEach(stock => {
            let reverse_ADX = stock.reverse_ADX;
            let reverse_EMA_high = stock.reverse_EMA_high;
            let reverse_EMA_hlc3 = stock.reverse_EMA_hlc3;
            let reverse_EMA_low = stock.reverse_EMA_low;

            let ten_reverse_ADX = reverse_ADX.filter((a, i) => {
              return i >= 0 && i <= 15;
            });
            let ten_reverse_EMA_high = reverse_EMA_high.filter((a, i) => {
              return i >= 0 && i <= 15;
            });
            let ten_reverse_EMA_hlc3 = reverse_EMA_hlc3.filter((a, i) => {
              return i >= 0 && i <= 15;
            });
            let ten_reverse_EMA_low = reverse_EMA_low.filter((a, i) => {
              return i >= 0 && i <= 15;
            });

            let ADX = ten_reverse_ADX.reverse();
            let EMA_high = ten_reverse_EMA_high.reverse();
            let EMA_hlc3 = ten_reverse_EMA_hlc3.reverse();
            let EMA_low = ten_reverse_EMA_low.reverse();

            stock.traded_shares =
              (stock.close_on_day.traded_shares /
                stock.close_on_day.total_quantity) *
              100;
            stock.ADX = ADX;
            stock.EMA_high = EMA_high;
            stock.EMA_hlc3 = EMA_hlc3;
            stock.EMA_low = EMA_low;
          });

          this.by_MA_EMA_ADX = recommendations.sort(
            (a, b) => parseFloat(b.traded_shares) - parseFloat(a.traded_shares)
          );

          this.section.MA_EMA_ADX.loaded = true;
        });
    },
    getRecommendationsByRsiNAdx() {
      this.by_RSI_ADX = {};
      this.section.RSI_ADX.loaded = false;

      axios
        .get("/api/get_recommendations_by_rsi_n_adx/" + this.forDateof)
        .then(response => {
          let data = response.data;

          data.buyRecommendations.forEach((stock, i) => {
            let reverse_RSI = stock.reverse_RSI;
            let reverse_ADX = stock.reverse_ADX;

            let ten_reverse_RSI = reverse_RSI.filter((a, i) => {
              return i >= 0 && i <= 15;
            });

            let ten_reverse_ADX = reverse_ADX.filter((a, i) => {
              return i >= 0 && i <= 15;
            });

            data["buyRecommendations"][i].reverse_RSI = ten_reverse_RSI.map(n =>
              n.toFixed(2)
            );
            data["buyRecommendations"][i].reverse_ADX = ten_reverse_ADX.map(n =>
              n.toFixed(2)
            );

            let RSI = ten_reverse_RSI.reverse();
            let ADX = ten_reverse_ADX.reverse();

            data["buyRecommendations"][i].RSI = RSI;
            data["buyRecommendations"][i].ADX = ADX;
          });
          this.by_RSI_ADX = data;

          this.section.RSI_ADX.loaded = true;
        });
    },
    getRecommendationsByRsiNMacd() {
      this.by_RSI_MACD = [];
      this.section.RSI_MACD.loaded = false;

      axios
        .get("/api/get_recommendations_by_rsi_n_macd/" + this.forDateof)
        .then(response => {
          let recommendations = response.data;

          Object.keys(recommendations).forEach(symbol => {
            let reverse_RSI = recommendations[symbol].reverse_RSI;
            let reverse_MACD = recommendations[symbol].reverse_MACD;
            let ten_reverse_RSI = reverse_RSI.filter((a, i) => {
              return i >= 0 && i <= 15;
            });
            let ten_reverse_MACD = reverse_MACD.filter((a, i) => {
              return i >= 0 && i <= 15;
            });

            recommendations[symbol].reverse_RSI = ten_reverse_RSI.map(n =>
              n.toFixed(2)
            );
            recommendations[symbol].reverse_MACD = ten_reverse_MACD.map(n =>
              n.toFixed(2)
            );

            let RSI = ten_reverse_RSI.reverse();
            let MACD = ten_reverse_MACD.reverse();

            recommendations[symbol].RSI = RSI;
            recommendations[symbol].MACD = MACD;
          });
          this.by_RSI_MACD = recommendations;

          this.section.RSI_MACD.loaded = true;
        });
    }
  },
  computed: {
    recommendationView() {
      return this.$store.state.recommendationView;
    },
    forDateof() {
      return this.$store.state.forDateof;
    }
  },
  watch: {
    forDateof: function(newDate) {
      this.initialize();
    }
  }
};
</script>
