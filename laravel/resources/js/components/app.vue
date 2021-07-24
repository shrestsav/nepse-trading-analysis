<template>
  <v-app>
    <v-navigation-drawer app :mini-variant="miniNavigationDrawer">
      <v-list-item>
        <v-list-item-icon
          @click="toggleNavigationDrawer"
          class="navigation-drawer-toggle"
        >
          <v-icon v-if="miniNavigationDrawer" title="Expand Navigation Drawer">
            mdi-forwardburger
          </v-icon>
          <v-icon v-else title="Contract Navigation Drawer">
            mdi-backburger
          </v-icon>
        </v-list-item-icon>
        <v-list-item-content>
          <v-list-item-title>
            <strong>TRADING ANALYSIS</strong>
          </v-list-item-title>
        </v-list-item-content>
      </v-list-item>

      <v-divider></v-divider>

      <v-list dense nav>
        <v-list-item
          v-for="(item, index) in navigations"
          :key="index"
          :to="item.path"
        >
          <v-list-item-icon :title="miniNavigationDrawer ? item.title : ''">
            <v-icon>mdi-{{ item.icon }}</v-icon>
          </v-list-item-icon>
          <v-list-item-content>
            <v-list-item-title v-html="item.title"></v-list-item-title>
          </v-list-item-content>
        </v-list-item>
      </v-list>
    </v-navigation-drawer>

    <v-app-bar app>
      <v-row align="center" v-if="routeName == 'recommendations'">
        <v-col cols="1">
          <v-btn-toggle :value="recommendationView" shaped mandatory>
            <v-btn
              value="tile"
              @click="changeRecommendationView('tile')"
              elevation="24"
            >
              <v-icon>mdi-view-module</v-icon>
            </v-btn>
            <v-btn
              value="list"
              @click="changeRecommendationView('list')"
              elevation="24"
            >
              <v-icon>mdi-format-list-bulleted-square</v-icon>
            </v-btn>
          </v-btn-toggle>
        </v-col>

        <v-spacer></v-spacer>

        <v-col cols="2">
          <v-menu
            v-model="displayDatePicker"
            :close-on-content-click="false"
            :nudge-right="40"
            transition="scale-transition"
            offset-y
            min-width="auto"
          >
            <template v-slot:activator="{ on, attrs }">
              <v-text-field
                v-model="forDateof"
                prepend-icon="mdi-calendar"
                readonly
                v-bind="attrs"
                v-on="on"
                hide-details
                >fsdf</v-text-field
              >
            </template>
            <v-date-picker
              v-model="forDateof"
              @input="displayDatePicker = false"
              landscape
            ></v-date-picker>
          </v-menu>
        </v-col>

        <v-spacer></v-spacer>

        <v-switch
          value
          :input-value="isLiveMarket"
          @change="changeIsLiveMarket"
          label="Live"
          hide-details
        ></v-switch>
      </v-row>
    </v-app-bar>
    <v-main>
      <v-container>
        <v-layout justify-center align-center>
          <router-view></router-view>
        </v-layout>
      </v-container>
    </v-main>

    <v-footer app>
      <!-- -->
    </v-footer>
  </v-app>
</template>


<script>
export default {
  components: {},
  data() {
    return {
      miniNavigationDrawer: false,
      navigations: [
        {
          path: "/dashboard/daily-recommendation",
          title: "Recommendations",
          icon: "trending-up"
        },
        {
          path: "/dashboard/sync-price-history",
          title: "Sync Price History",
          icon: "cloud-sync"
        }
      ],
      displayDatePicker: false
    };
  },
  mounted() {
    if (localStorage.miniNavigationDrawer) {
      this.miniNavigationDrawer =
        localStorage.miniNavigationDrawer == "true" ? true : false;
    }
  },
  methods: {
    changeRecommendationView(view) {
      this.$store.commit("changeRecommendationView", view);
    },
    changeIsLiveMarket(bool) {
      this.$store.commit("changeIsLiveMarket", bool || false);
    },
    toggleNavigationDrawer() {
      this.miniNavigationDrawer = !this.miniNavigationDrawer;
      localStorage.miniNavigationDrawer = this.miniNavigationDrawer;
    }
  },
  computed: {
    recommendationView() {
      return this.$store.state.recommendationView;
    },
    routeName() {
      return this.$route.name;
    },
    isLiveMarket() {
      return this.$store.state.isLiveMarket;
    },
    forDateof: {
      get: function() {
        return this.$store.state.forDateof;
      },
      set: function(selectedDate) {
        this.$store.commit("changeForDateof", selectedDate);
      }
    }
  }
};
</script>
