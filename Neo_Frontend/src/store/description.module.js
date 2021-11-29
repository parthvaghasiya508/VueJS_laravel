/* eslint-disable no-shadow */

import ApiService from '@/services/api.service';
import { apiEndpoint } from '@/shared';
import { PMSError, ValidationError } from '@/errors';

const endpoint = `${apiEndpoint}/description`;

const state = {
  descriptions: null,
  pending: false,
  pmsError: null,
};

const getters = {
  loaded: (state) => state.descriptions !== null,
};

const mutations = {
  clearErrors() {
    state.pmsError = null;
  },
  beforeRequest() {
    state.pending = true;
  },
  fetched(state, descriptions = {}) {
    state.descriptions = descriptions;
    state.pending = false;
  },
  updated(state, descriptions = {}) {
    if (descriptions !== null) state.descriptions = descriptions;
    state.pending = false;
  },
  error(state, error) {
    state.pmsError = error;
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
  async getDescription({ commit }) {
    commit('beforeRequest');
    try {
      const { data } = await ApiService.get(`${endpoint}`);
      commit('fetched', data);
      return data;
    } catch (error) {
      commit('fetched');
      commit('error', error);
      return null;
    }
  },
  async updateDescription({ commit }, descriptions) {
    commit('beforeRequest');
    try {
      await ApiService.put(`${endpoint}`, descriptions);
      commit('updated', { descriptions });
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
