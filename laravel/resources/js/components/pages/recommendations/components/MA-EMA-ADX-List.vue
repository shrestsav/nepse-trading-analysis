<template>
  <v-row>
    <v-col cols="12">
      <v-simple-table>
        <template v-slot:default>
          <thead>
            <tr>
              <th class="text-left">S.No</th>
              <th class="text-left">Stock</th>
              <th class="text-left">Traded Shares</th>
              <th class="text-left">Price on Day</th>
              <th class="text-left">Price Today</th>
              <th class="text-left">Change</th>
              <th class="text-left">EMA High</th>
              <th class="text-left" width="12%"></th>
              <th class="text-left">ADX</th>
              <th class="text-left">ADX Signal</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(stock, i) in by_MA_EMA_ADX" :key="i">
              <td>{{ i + 1 }}</td>
              <td :title="stock.stock.company_name">
                {{ stock.stock.symbol }}
              </td>
              <td
                :title="
                  stock.close_on_day.traded_shares +
                    ' out of ' +
                    stock.close_on_day.total_quantity +
                    ' traded'
                "
              >
                {{ stock.traded_shares.toFixed(2) }} %
              </td>
              <td>
                {{ stock.close_on_day.closing_price }}
              </td>
              <td>
                {{ stock.close_today.closing_price }}
              </td>
              <td>
                {{
                  (
                    ((stock.close_today.closing_price -
                      stock.close_on_day.closing_price) /
                      stock.close_on_day.closing_price) *
                    100
                  ).toFixed(2)
                }}
                %
              </td>
              <td>
                <div class="font-weight-normal caption">
                  {{ stock.reverse_EMA_high[2] }}
                  <v-icon
                    v-if="stock.reverse_EMA_high[2] > stock.reverse_EMA_high[1]"
                  >
                    mdi-arrow-bottom-right-thick
                  </v-icon>
                  <v-icon v-else>mdi-arrow-top-right-thick</v-icon>
                  {{ stock.reverse_EMA_high[1] }}
                  <v-icon>mdi-arrow-top-right-thick</v-icon>
                  {{ stock.reverse_EMA_high[0] }}
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
                  <v-icon>
                    <template
                      v-if="stock.reverse_ADX[2] > stock.reverse_ADX[1]"
                    >
                      mdi-arrow-bottom-right-thick
                    </template>
                    <template v-else>mdi-arrow-top-right-thick</template>
                  </v-icon>
                  {{ stock.reverse_ADX[1] }}
                  <v-icon>
                    <template
                      v-if="stock.reverse_ADX[1] > stock.reverse_ADX[0]"
                    >
                      mdi-arrow-bottom-right-thick
                    </template>
                    <template v-else>mdi-arrow-top-right-thick</template>
                  </v-icon>
                  {{ stock.reverse_ADX[0] }}
                </div>
              </td>
              <td>
                {{ stock.adx_diff > 0 ? '+' : '-' }}
                {{ stock.adx_diff.toFixed(2) }}
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
    by_MA_EMA_ADX: { type: Array, required: true },
    sparkline: { type: Object, required: true }
  },
  data() {
    return {}
  },
  mounted() {},
  methods: {}
}
</script>
