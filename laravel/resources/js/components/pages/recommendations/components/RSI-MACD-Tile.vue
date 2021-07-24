<template>
  <v-row>
    <v-col
      v-for="(stock, symbol, i) in by_RSI_MACD"
      :key="i"
      cols="12"
      md="3"
      xl="2"
    >
      <v-card width="400">
        <v-sparkline
          :value="stock.RSI"
          :gradient="sparkline.gradient"
          :smooth="sparkline.radius || false"
          :padding="sparkline.padding"
          :line-width="sparkline.width"
          :stroke-linecap="sparkline.lineCap"
          :gradient-direction="sparkline.gradientDirection"
          :fill="sparkline.fill"
          :type="sparkline.type"
          :auto-line-width="sparkline.autoLineWidth"
          auto-draw
        ></v-sparkline>
        <v-sparkline
          :value="stock.MACD"
          :gradient="sparkline.gradient"
          :smooth="sparkline.radius || false"
          :padding="sparkline.padding"
          :line-width="sparkline.width"
          :stroke-linecap="sparkline.lineCap"
          :gradient-direction="sparkline.gradientDirection"
          :fill="sparkline.fill"
          :type="sparkline.type"
          :auto-line-width="sparkline.autoLineWidth"
          auto-draw
        ></v-sparkline>
        <v-card-title>{{ symbol }}</v-card-title>
        <v-card-subtitle :title="stock.stock.company_name">
          <small>{{ companyName(stock.stock.company_name) }}</small>
        </v-card-subtitle>
        <v-card-text>
          <div>
            <div class="font-weight-normal caption">
              <strong>RSI: &nbsp;</strong>
              {{ stock.reverse_RSI[2] }}
              <v-icon v-if="stock.reverse_RSI[2] > stock.reverse_RSI[1]">
                mdi-arrow-bottom-right-thick
              </v-icon>
              <v-icon v-else>mdi-arrow-top-right-thick</v-icon>
              {{ stock.reverse_RSI[1] }}
              <v-icon>mdi-arrow-top-right-thick</v-icon>
              {{ stock.reverse_RSI[0] }}
            </div>
          </div>
          <div>
            <div class="font-weight-normal caption">
              <strong>MACD: &nbsp;</strong>
              {{ stock.reverse_MACD[2] }}
              <v-icon v-if="stock.reverse_MACD[2] > stock.reverse_MACD[1]">
                mdi-arrow-bottom-right-thick
              </v-icon>
              <v-icon v-else>mdi-arrow-top-right-thick</v-icon>
              {{ stock.reverse_MACD[1] }}
              <v-icon>mdi-arrow-top-right-thick</v-icon>
              {{ stock.reverse_MACD[0] }}
            </div>
          </div>
        </v-card-text>
      </v-card>
    </v-col>
  </v-row>
</template>

<script>
export default {
  name: 'RsiAdxList',
  components: {},
  props: {
    by_RSI_MACD: { type: Object, required: true },
    sparkline: { type: Object, required: true }
  },
  data() {
    return {}
  },
  mounted() {},
  methods: {
    companyName(companyName) {
      if (companyName.length > 40) {
        return companyName.substring(0, 40) + ' ...'
      }
      return companyName
    }
  }
}
</script>
