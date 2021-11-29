/* eslint-disable no-shadow */

import ApiService from '@/services/api.service';
import { apiEndpoint } from '@/shared';

const endpoint = `${apiEndpoint}/role`;

const state = {
  role: null,
  invite: null,
  loading: false,
  error: null,
};

const getters = {

};

const mutations = {
  getRole(state, data) {
    state.role = data;
  },
};

const actions = {
  async getRole({ commit }) {
    try {
      const response = await ApiService.get(`${endpoint}`, {
      });
      commit('getRole', response.data);
      return response.data;
    } catch (error) {
      commit('getRole', null);
      return null;
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
