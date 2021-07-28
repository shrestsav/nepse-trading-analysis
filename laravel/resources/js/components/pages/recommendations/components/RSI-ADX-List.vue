<template>
  <v-row>
    <v-col cols="12">
      <v-simple-table>
        <template v-slot:default>
          <thead>
            <tr>
              <th class="text-left">S.No</th>
              <th class="text-left">Stock</th>
              <th class="text-left">Price on Day</th>
              <template v-if="!isSelectedForToday">
                <th class="text-left">Price Today</th>
                <th class="text-left">Change</th>
              </template>
              <th class="text-left" width="12%"></th>
              <th class="text-left">RSI</th>
              <th class="text-left" width="12%"></th>
              <th class="text-left">ADX</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(stock, i) in buyRecommendations" :key="i">
              <td>{{ i + 1 }}</td>
              <td :title="stock.stock.company_name">
                {{ stock.stock.symbol }}
              </td>
              <td>{{ stock.close_on_day }}</td>
              <template v-if="!isSelectedForToday">
                <td>{{ stock.close_today.closing_price }}</td>
                <td>
                  {{
                    (
                      ((stock.close_today.closing_price - stock.close_on_day) /
                        stock.close_on_day) *
                      100
                    ).toFixed(2)
                  }}
                  %
                </td>
              </template>
              <td>
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
              </td>
              <td>
                <div class="font-weight-normal caption">
                  {{ stock.reverse_RSI[2] }}
                  <v-icon v-if="stock.reverse_RSI[2] > stock.reverse_RSI[1]">
                    mdi-arrow-bottom-right-thick
                  </v-icon>
                  <v-icon v-else>mdi-arrow-top-right-thick</v-icon>
                  {{ stock.reverse_RSI[1] }}
                  <v-icon>mdi-arrow-top-right-thick</v-icon>
                  {{ stock.reverse_RSI[0] }}
                </div>
              </td>

              <td>
                <v-sparkline
                  :value="stock.ADX"
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
              </td>
              <td>
                <div class="font-weight-normal">
                  {{ stock.reverse_ADX[2] }}
                  <v-icon v-if="stock.reverse_ADX[2] > stock.reverse_ADX[1]">
                    mdi-arrow-bottom-right-thick
                  </v-icon>
                  <v-icon v-else>mdi-arrow-top-right-thick</v-icon>
                  {{ stock.reverse_ADX[1] }}
                  <v-icon>mdi-arrow-top-right-thick</v-icon>
                  {{ stock.reverse_ADX[0] }}
                </div>
              </td>
            </tr>
          </tbody>
        </template>
      </v-simple-table>
    </v-col>
  </v-row>
</template>

<script>
export default {
  name: 'RsiAdxList',
  components: {},
  props: {
    by_RSI_ADX: { type: Object, required: true },
    sparkline: { type: Object, required: true },
  },
  data() {
    return {}
  },
  mounted() {},
  methods: {},
  computed: {
    buyRecommendations() {
      return this.by_RSI_ADX.buyRecommendations
    },
    isSelectedForToday() {
      return this.$store.state.forDateof == this.$moment().format('YYYY-MM-DD')
    },
  },
}
</script>
