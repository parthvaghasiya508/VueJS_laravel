/* eslint-disable no-shadow */

import ApiService from '@/services/api.service';
import { apiEndpoint } from '@/shared';

const state = {
  data: null,
  pending: false,
  error: null,
  updateError: null,
};

const getters = {
  data: (state) => state.data,
};

const mutations = {
  clearErrors(state) {
    state.error = null;
    state.updateError = null;
  },
  beforeLoading(state, initial = false) {
    if (initial) {
      state.data = null;
    }
    state.error = null;
    state.pending = true;
  },
  error(state, error) {
    state.pending = false;
    state.error = error;
  },
  data(state, invoices) {
    state.pending = false;
    state.data = invoices;
  },
};

const actions = {
  async fetchData({ commit }, { page, limit, forced = false }) {
    commit('beforeLoading', forced);
    try {
      const response = await ApiService.post(`${apiEndpoint}/invoices`, { page, limit });
      commit('data', response.data);
    } catch (error) {
      commit('error', error);
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
