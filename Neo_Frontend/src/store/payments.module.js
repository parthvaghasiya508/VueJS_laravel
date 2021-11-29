/* eslint-disable no-shadow */

import ApiService from '@/services/api.service';
import { apiEndpoint } from '@/shared';

const endpoint = `${apiEndpoint}/payments`;

const state = {
  paymentMethods: [],
  defaultPaymentMethod: null,
  intent: null,
  pending: false,
  error: null,
  creditorId: null,
  paymentMethodDetails: null,
  paymentMethodDetailsPending: false,
};

const getters = {
  loaded: (state) => (state.methods != null && !state.pending),
  paymentMethods: (state) => (state.paymentMethods != null ? state.paymentMethods : []),
  defaultPaymentMethod: (state) => (state.defaultPaymentMethod != null ? state.defaultPaymentMethod : null),
  intent: (state) => (state.intent != null ? state.intent : null),
  creditorId: (state) => (state.creditorId != null ? state.creditorId : null),
  pending: (state) => state.pending,
  paymentMethodDetails: (state) => (state.paymentMethodDetails != null ? state.paymentMethodDetails : null),
};

const mutations = {
  clearErrors() {
    // do nothing
  },
  beforeLoading(state) {
    state.pending = true;
    state.paymentMethods = null;
    state.defaultPaymentMethod = null;
    state.intent = null;
    state.creditorId = null;
  },
  data(state, payload = {}) {
    state.pending = false;
    state.paymentMethods = payload.paymentMethods;
    state.defaultPaymentMethod = payload.defaultPaymentMethod;
    state.intent = payload.intent;
    state.error = payload.error;
    state.creditorId = payload.creditorId;
  },
  beforeFetchPaymentMethod(state) {
    state.paymentMethodDetailsPending = true;
    state.paymentMethodDetails = null;
  },
  paymentMethodLoaded(state, payload = {}) {
    state.paymentMethodDetailsPending = false;
    state.error = payload.error;
    state.paymentMethodDetails = payload.paymentMethod;
  },
  updatePaymentMethod(state, payload = {}) {
    state.paymentMethods = payload.paymentMethod;
    state.intent = payload.setupIntent;
  },
};

const actions = {
  async fetchData({ commit }) {
    commit('beforeLoading');
    try {
      const { data } = await ApiService.get(`${endpoint}`);
      commit('data', data);
    } catch (error) {
      commit('data', { error });
    }
  },
  async fetchPaymentMethod({ commit }, { paymentMethod }) {
    commit('beforeFetchPaymentMethod');
    try {
      const { data } = await ApiService.get(`${endpoint}/payment-method/details/${paymentMethod}`);
      commit('paymentMethodLoaded', data);
    } catch (error) {
      commit('paymentMethodLoaded', { error });
    }
  },
  async savePaymentMethod({ commit }, obj) {
    try {
      const result = await ApiService.post(`${endpoint}`, obj);
      commit('updatePaymentMethod', result.data);
    } catch (error) {
      // eslint-disable-next-line no-console
      console.error(error);
    }
  },
};

export default {
  namespaced: true,
  state,
  getters,
  mutations,
  actions,
};
