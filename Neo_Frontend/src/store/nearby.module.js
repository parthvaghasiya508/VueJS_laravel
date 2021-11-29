/* eslint-disable no-shadow */

import ApiService from '@/services/api.service';
import { apiEndpoint } from '@/shared';
import { PMSError, ValidationError } from '@/errors';

const endpoint = `${apiEndpoint}/nearby`;

const state = {
  airports: null,
  trains: null,
  motors: null,
  publics: null,
  city_centers: null,
  pending: false,
};

const getters = {
  pending: (state) => state.pending,
  loaded: (state) => state.airports !== null || state.trains !== null || state.motors !== null || state.publics !== null
  || state.city_centers !== null,
};

const mutations = {
  clearErrors() {
    //
  },
  beforeRequest() {
    state.pending = true;
  },
  fetched(state, {
    airports = null, trains = null, motors = null, publics = null, cityCenters = null,
  } = {}) {
    state.airports = airports;
    state.trains = trains;
    state.motors = motors;
    state.publics = publics;
    state.city_centers = cityCenters;
    state.pending = false;
  },
  updated(state, {
    airports = null, trains = null, motors = null, publics = null, cityCenters = null,
  } = {}) {
    if (airports !== null) state.airports = airports;
    if (trains !== null) state.trains = trains;
    if (motors !== null) state.motors = motors;
    if (publics !== null) state.publics = publics;
    if (cityCenters !== null) state.city_centers = cityCenters;
    state.pending = false;
  },
};

function handleApiError(commit, error) {
  const { status, data } = error.response;
  switch (status) {
    case 409: // PMSError
      throw new PMSError(status, data.message);
    case 422:
      throw new ValidationError(status, data.message, data.errors);
    default:
      throw error;
  }
}

const actions = {
  async getNearby({ commit }) {
    commit('beforeRequest');
    try {
      const { data } = await ApiService.get(`${endpoint}`);
      commit('fetched', data);
    } catch (error) {
      commit('fetched');
      handleApiError(commit, error);
    }
  },
  async updateNearby({ commit }, nearby) {
    commit('beforeRequest');
    try {
      await ApiService.put(`${endpoint}`, nearby);
      commit('updated', { nearby });
    } catch (error) {
      commit('updated');
      handleApiError(commit, error);
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
