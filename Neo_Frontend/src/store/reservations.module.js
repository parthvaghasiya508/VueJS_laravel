/* eslint-disable no-shadow */

import ApiService from '@/services/api.service';
import { apiEndpoint } from '@/shared';
import { PMSError, ValidationError } from '@/errors';

const endpoint = `${apiEndpoint}/reservations`;

const state = {
  data: null,
  pending: false,
  error: null,
  pmsError: null,
  iframe: null,
};

const getters = {
  loaded: (state) => state.data != null,
  empty: (state) => state.data.list != null && !state.data.list.length,
  iframeLoaded: (state) => state.iframe != null,
};

const mutations = {
  clearErrors(state) {
    state.error = null;
  },
  beforeLoading(state) {
    state.error = null;
    state.pending = true;
  },
  error(state, error) {
    state.pending = false;
    state.error = error;
    state.pmsError = error;
  },
  data(state, data) {
    state.pending = false;
    state.data = data;
  },
  update(state, booking) {
    const record = state.data.list.find((b) => b.id === booking.id);
    if (record != null) {
      Object.keys(booking).forEach((k) => {
        record[k] = booking[k];
      });
    }
    state.pending = false;
  },
  fetchIframe(state, data) {
    state.pending = false;
    state.iframe = data;
  },
};

function handleApiError(commit, error) {
  const { status, data } = error.response;
  let err = error;
  switch (status) {
    case 422:
      err = new ValidationError(status, data.message, data.errors);
      break;
    case 409: // PMSError
      err = new PMSError(status, data.message);
      break;
    default:
      break;
  }
  commit('error', err);
  throw err;
}

const actions = {
  async fetchData({ commit }, payload) {
    commit('beforeLoading');
    try {
      const response = await ApiService.post(`${endpoint}`, payload);
      commit('data', response.data);
    } catch (error) {
      commit('error', error);
    }
  },
  async cancelReservation({ commit }, payload) {
    commit('beforeLoading');
    try {
      const response = await ApiService.patch(`${endpoint}/cancel`, payload);
      commit('update', response.data);
    } catch (error) {
      handleApiError(commit, error);
    }
  },
  async fetchIframe({ commit }, payload) {
    commit('beforeLoading');
    try {
      const response = await ApiService.post(`${endpoint}/card-details`, payload);
      commit('fetchIframe', response.data);
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
