/* eslint-disable no-shadow */

import ApiService from '@/services/api.service';
import { apiEndpoint } from '@/shared';
import {
  ValidationError, PMSError,
} from '@/errors';

const endpoint = `${apiEndpoint}/channelshealth`;

const state = {
  channelshealth: [],
  loaded: false,
};

const getters = {
  channelshealth: (state) => state.channelshealth,
  loaded: (state) => state.loaded,
};

const mutations = {
  clearErrors(state) {
    state.validationError = null;
  },
  onLoad() {
    state.loaded = true;
  },
  fetched(state, channelshealth) {
    state.channelshealth = Object.values(channelshealth);
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
  async onLoad({ commit }) {
    commit('onLoad');
  },

  async getChannelshealth({ commit }) {
    try {
      const { data } = await ApiService.get(`${endpoint}`);
      commit('fetched', data);
    } catch (error) {
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
