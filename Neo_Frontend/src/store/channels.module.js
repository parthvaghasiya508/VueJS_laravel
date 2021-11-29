/* eslint-disable no-shadow */

import Vue from 'vue';
import ApiService from '@/services/api.service';
import { apiEndpoint } from '@/shared';
import { PMSError, ValidationError } from '@/errors';

const endpoint = `${apiEndpoint}/channels`;

const state = {
  data: null,
  pending: false,
  pmsError: null,
  error: null,
  updatePending: false,
  updateError: null,
  tab: null,
  // filter: '',
};

const getters = {
  loaded: (state) => !state.pending && state.data != null,
  channels: (state, getters) => (getters.loaded ? state.data.channels : []),
  types: (state, getters) => (getters.loaded ? state.data.types : []),
  plans: (state, getters) => (getters.loaded ? state.data.plans : []),
  activeTab: (state) => state.tab || 'connected',
  // filter: (state) => state.filter || '',
};

const mutations = {
  clearErrors(state) {
    state.error = null;
    state.updateError = null;
  },
  clearPageData(state, clear = false) {
    if (clear) {
      state.tab = null;
    }
    // state.filter = '';
  },
  setActiveTab(state, tab) {
    state.tab = tab;
  },
  // setFilter(state, filter) {
  //   state.filter = filter;
  // },
  beforeLoading(state, initial = false) {
    if (initial) {
      state.data = null;
    }
    state.error = null;
    state.pending = true;
  },
  beforeLoadingChannel(state, id) {
    if (state.channelsData == null) {
      state.channelsData = {};
    }
    if (id in state.channelsData) {
      Vue.delete(state.channelsData, id);
    }
    state.error = null;
    state.pending = true;
  },
  beforeUpdate(state) {
    state.updateError = null;
    state.updatePending = true;
  },
  data(state, { data = null, error = null } = {}) {
    state.pending = false;
    state.data = data;
    state.error = error;
    state.pmsError = error;
  },
  channelsData(state, { id = null, data = null, error = null } = {}) {
    state.pending = false;
    state.channelsData[id] = data;
    state.error = error;
  },
  modified(state, { channel = null, error = null } = {}) {
    if (state.data == null || state.data.channels == null) return;
    if (channel != null) {
      const idx = state.data.channels.findIndex((p) => p.id === channel.id);
      if (idx !== -1) {
        Vue.set(state.data.channels, idx, JSON.parse(JSON.stringify(channel)));
      }
    }
    state.updatePending = false;
    state.updateError = error;
  },
  mappings(state, { channel, rooms }) {
    if (state.data == null || state.data.channels == null) return;
    const ch = state.data.channels.find(({ id }) => id === channel.id);
    if (ch == null) return;
    rooms.forEach(({ id: rid, inv }) => {
      const plan = state.data.plans.find(({ id }) => id === rid);
      if (plan == null) return;
      if (inv) {
        plan.marketcodes.push(ch.id);
      } else {
        const idx = plan.marketcodes.indexOf(ch.id);
        if (idx !== -1) {
          plan.marketcodes.splice(idx, 1);
        }
      }
    });
    ch.count = state.data.plans.reduce((a, { marketcodes }) => a + (marketcodes.includes(ch.id) ? 1 : 0), 0);
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
    commit('beforeLoading', forced);
    try {
      const { data } = await ApiService.get(`${endpoint}`);
      commit('data', { data });
    } catch (error) {
      commit('data', { error });
    }
  },
  async channelState({ commit }, { id, mode }) {
    commit('beforeUpdate');
    try {
      const { data: channel } = await ApiService.patch(`${endpoint}/${id}`, { mode });
      commit('modified', { channel });
    } catch (error) {
      commit('modified', { error });
      throw handleApiError(commit, error);
    }
  },
  async channelActivate({ commit }, payload) {
    const { id } = payload;
    commit('beforeUpdate');
    try {
      const { data: channel } = await ApiService.patch(`${endpoint}/${id}`, payload);
      commit('modified', { channel });
    } catch (error) {
      commit('modified', { error });
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
