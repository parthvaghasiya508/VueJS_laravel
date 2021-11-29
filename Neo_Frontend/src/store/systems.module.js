/* eslint-disable no-shadow */

import Vue from 'vue';
import ApiService from '@/services/api.service';
import { apiEndpoint } from '@/shared';
import { PMSError, ValidationError } from '@/errors';
import StorageService from '@/services/storage.service';

const endpoint = `${apiEndpoint}/systems`;
const initialHotel = StorageService.getHotel();

const state = {
  data: null,
  pending: false,
  error: null,
  updatePending: false,
  updateError: null,
  validationError: null,
  apaleoHotelKey: '',
  connected: 'no',
  authUrl: '',
  clientSecretValid: '',
};

const getters = {
  loaded: (state) => !state.pending && state.data != null,
  systems: (state, getters) => getters.loaded && state.data,
  validationError: (state) => state.validationError,
  connection: (state) => state.connected,
};

const mutations = {
  clearErrors(state) {
    state.error = null;
    state.updateError = null;
    state.validationError = null;
  },
  beforeLoading(state, initial = false) {
    if (initial) {
      state.data = null;
    }
    state.error = null;
    state.pending = true;
  },
  beforeUpdate(state) {
    state.updateError = null;
    state.updatePending = true;
    state.validationError = null;
  },
  afterUpdate(state) {
    state.updatePending = false;
  },
  data(state, { data = null, error = null } = {}) {
    state.pending = false;
    state.data = data;
    state.error = error;
  },
  modified(state, { system = null, software, error = null } = {}) {
    const o = state.data.find((p) => p.active != null);
    const same = o != null && o.id === system;
    if (o != null && !same) {
      Vue.delete(o, 'active');
    }
    if (system != null) {
      const n = same ? o : state.data.find((p) => p.id === system);
      if (n != null) {
        Vue.set(n, 'active', software);
      }
    }
    state.updatePending = false;
    state.updateError = error;
  },
  validationError(state, error) {
    state.validationError = error;
    state.pending = false;
  },
  setapaleoHotelKey(state, newValue) {
    state.apaleoHotelKey = newValue;
  },
  setStatus(state, { status }) {
    state.connected = status;
  },
  setAuthUrl(state, { data }) {
    state.authUrl = data;
  },
  setclientSecretValid(state, { valid }) {
    state.clientSecretValid = valid;
  },
};

function handleApiError(commit, error) {
  const { status, data } = error.response;
  let err = error;
  switch (status) {
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
  return err;
}

const actions = {
  async fetchData({ commit, state }, forced = false) {
    if (!forced && state.data != null) return;
    commit('beforeLoading');
    try {
      const { data } = await ApiService.get(`${endpoint}`);
      commit('data', { data });
    } catch (error) {
      commit('data', { error });
    }
  },
  async getAuthUrl({ commit }) {
    try {
      const { data } = await ApiService.post(`${apiEndpoint}/apaleo/connect`, { object_id: initialHotel });
      commit('setAuthUrl', { data });
    } catch (error) {
      handleApiError(error);
    }
  },
  async systemState({ commit }, { system = null, software = null } = {}) {
    commit('beforeUpdate');
    try {
      if (system && software) {
        await ApiService.post(`${endpoint}`, { system, software });
      } else {
        await ApiService.post(`${endpoint}`);
      }
      commit('modified', { system, software });
    } catch (error) {
      throw handleApiError(commit, error);
    }
  },
  async apaleoObjectMap() {
    try {
      await ApiService.post(`${apiEndpoint}/apaleo/object-map`, { apaleoHotelKey: state.apaleoHotelKey, object_id: initialHotel });
    } catch (error) {
      handleApiError(error);
    }
  },
  setapaleoHotelKey({ commit }, newValue) {
    commit('setapaleoHotelKey', newValue);
  },
  async revokeApaleoConnection() {
    try {
      await ApiService.post(`${apiEndpoint}/apaleo/revoke`, { object_id: initialHotel });
    } catch (error) {
      handleApiError(error);
    }
  },
  async checkConnection({ commit, dispatch }) {
    try {
      const { data } = await ApiService.post(`${apiEndpoint}/apaleo/status`, { object_id: initialHotel });
      let status = '';
      if (data.status === 1) {
        status = 'yes';
      } else {
        status = 'no';
        dispatch('getAuthUrl');
      }
      commit('setStatus', { status });
    } catch (error) {
      handleApiError(error);
    }
  },
  async hostawaySetClientSecret({ commit }, hostawayAccountID) {
    try {
      // console.log(initialHotel);
      commit('beforeUpdate');
      const { data } = await ApiService.post(`${apiEndpoint}/hostaway/client-secret`, { object_id: initialHotel, account_id: hostawayAccountID.hostawayAccountID, api_key: hostawayAccountID.hostawayAPIKey });
      const valid = data.success;
      commit('setclientSecretValid', { valid });
      commit('afterUpdate');
    } catch (error) {
      handleApiError(error);
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
