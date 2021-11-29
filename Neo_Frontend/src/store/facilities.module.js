/* eslint-disable no-shadow */

import ApiService from '@/services/api.service';
import { apiEndpoint } from '@/shared';
import { PMSError, ValidationError } from '@/errors';

const endpoint = `${apiEndpoint}/facilities`;

const state = {
  available: null,
  facilities: null,
  pending: false,
};

const getters = {
  pending: (state) => state.pending,
  loaded: (state) => (state.facilities != null && state.available != null),
};

const mutations = {
  clearErrors() {
    //
  },
  beforeRequest() {
    state.pending = true;
  },
  fetched(state, { facilities = null, available = null } = {}) {
    state.facilities = facilities;
    state.available = available;
    state.pending = false;
  },
  updated(state, { facilities = null } = {}) {
    if (facilities != null) state.facilities = facilities;
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
  async getFacilities({ commit }) {
    commit('beforeRequest');
    try {
      const { data } = await ApiService.get(`${endpoint}`);
      commit('fetched', data);
    } catch (error) {
      commit('fetched');
      handleApiError(commit, error);
    }
  },
  async updateFacilities({ commit }, facilities) {
    commit('beforeRequest');
    try {
      await ApiService.put(`${endpoint}`, { facilities });
      commit('updated', { facilities });
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
