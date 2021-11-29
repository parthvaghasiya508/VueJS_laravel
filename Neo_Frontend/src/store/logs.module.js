/* eslint-disable no-shadow */

// import Vue from 'vue';
import ApiService from '@/services/api.service';
import { apiEndpoint } from '@/shared';
import { HttpError, PMSError, ValidationError } from '@/errors';

const endpoint = `${apiEndpoint}/logs`;

const state = {
  booking: null,
  pending: false,
};

const getters = {
  loaded: (state) => (key) => (!state.pending && state[key] != null),
  booking: (state, getters) => (getters.loaded('booking') ? state.booking : []),
};

const mutations = {
  clearErrors() {
    //
  },
  beforeLoading(state, key, forced = false) {
    if (forced) {
      state[key] = null;
    }
    state.pending = true;
  },
  data(state, { key, data = null } = {}) {
    state.pending = false;
    state[key] = data;
  },
  insert(state, { key, data = null } = {}) {
    if (state[key] == null || data == null) return;
    state[key].unshift(data);
  },
};

function handleApiError(commit, error) {
  const { status, data } = error.response;
  let err = error;
  switch (status) {
    case 400: // BadRequest
      err = new HttpError(status, data.message);
      break;
    case 409: // PMSError
      err = new PMSError(status, data.message);
      break;
    case 422:
      err = new ValidationError(status, data.message, data.errors);
      break;
    case 500:
      err = new Error(data.message);
      break;
    default:
      break;
  }
  // commit('update', err);
  throw err;
}

function prepare(payload) {
  let path;
  switch (payload.key) {
    case 'booking':
      path = `hotels/${payload.id}/status`;
      break;
    default:
      path = null;
  }
  return path;
}

const actions = {
  async fetchLogs({ commit }, payload = {}) {
    const { key, forced = false } = payload;
    const path = prepare(payload);
    if (path == null) return;
    commit('beforeLoading', { key, forced });
    try {
      const { data } = await ApiService.get(`${endpoint}/${path}`);
      commit('data', { key, data });
    } catch (error) {
      commit('data', { error });
      throw handleApiError(commit, error);
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
