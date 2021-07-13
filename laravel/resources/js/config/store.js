import Vue from "vue";
import Vuex from "vuex";

Vue.use(Vuex);

export const store = new Vuex.Store({
    state: {
        recommendationView: "list",
        isLiveMarket: false,
        forDateof: new Date(Date.now() - new Date().getTimezoneOffset() * 60000)
            .toISOString()
            .substr(0, 10)
    },
    mutations: {
        changeRecommendationView(state, recommendationView) {
            state.recommendationView = recommendationView;
        },
        changeIsLiveMarket(state, isLiveMarket) {
            state.isLiveMarket = isLiveMarket;
        },
        changeForDateof(state, forDateof) {
            state.forDateof = forDateof;
        }
    },
    actions: {}
});
