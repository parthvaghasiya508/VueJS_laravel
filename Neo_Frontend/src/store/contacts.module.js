/* eslint-disable no-shadow */

import Vue from 'vue';
import ApiService from '@/services/api.service';
import { apiEndpoint } from '@/shared';
import {
  ValidationError, PMSError,
} from '@/errors';

const endpoint = `${apiEndpoint}/contacts`;

const state = {
  contacts: [],
  loaded: false,
  pmsError: null,
  validationError: null,
  updatePending: false,
};

const getters = {
  contacts: (state) => state.contacts,
  updatePending: (state) => state.updatePending,
  loaded: (state) => state.loaded,
  validationError: (state) => state.validationError,
};

const mutations = {
  clearErrors(state) {
    state.validationError = null;
  },
  onLoad() {
    state.loaded = true;
  },
  beforeRequest() {
    state.validationError = null;
    state.updatePending = true;
  },
  requestComplete() {
    state.updatePending = false;
  },
  error(state, error) {
    state.pmsError = error;
  },
  validationError(state, error) {
    state.validationError = error;
    state.pending = false;
  },
  fetched(state, contacts) {
    state.contacts = Object.values(contacts);
  },
  updated(state, contact) {
    const idx = state.contacts.findIndex((p) => p.id === contact.id);
    if (idx !== -1) {
      Vue.set(state.contacts, idx, JSON.parse(JSON.stringify(contact)));
    }
  },
  deleted(state, id) {
    const idx = state.contacts.findIndex((contact) => contact.id === id);
    if (idx !== -1) {
      state.contacts.splice(idx, 1);
    }
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

  async getContacts({ commit }) {
    commit('beforeRequest');
    try {
      const { data } = await ApiService.get(`${endpoint}`);
      commit('fetched', data);
      commit('requestComplete');
    } catch (error) {
      commit('requestComplete');
      commit('error', error);
      handleApiError(commit, error);
    }
  },

  async updateContact({ commit }, contact) {
    commit('beforeRequest');
    try {
      await ApiService.put(`${endpoint}/${contact.id}`, contact);
      commit('updated', contact);
      commit('requestComplete');
    } catch (error) {
      commit('requestComplete');
      handleApiError(commit, error);
    }
  },

  async deleteContact({ commit }, contactId) {
    commit('beforeRequest');
    try {
      await ApiService.delete(`${endpoint}/${contactId}`);
      commit('deleted', contactId);
      commit('requestComplete');
    } catch (error) {
      commit('requestComplete');
      handleApiError(commit, error);
    }
  },

  async createContact({ commit }, contact) {
    commit('beforeRequest');
    try {
      const { data } = await ApiService.post(`${endpoint}`, contact);
      commit('fetched', data);
      commit('requestComplete');
    } catch (error) {
      commit('requestComplete');
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
