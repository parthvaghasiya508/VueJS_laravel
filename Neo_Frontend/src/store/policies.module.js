/* eslint-disable no-shadow */

import ApiService from '@/services/api.service';
import { apiEndpoint } from '@/shared';
import { ValidationError } from '@/errors';

const endpoint = `${apiEndpoint}/policies`;

const state = {
  data: null,
  pending: false,
  error: null,
  pmsError: null,
  updatePending: false,
  updateError: null,
};

const getters = {
  loaded: (state) => !state.pending && state.data != null,
  cxlPols: (state, getters) => (getters.loaded ? state.data.cxlPols : []),
  pymtPols: (state, getters) => (getters.loaded ? state.data.pymtPols : []),
  bgarants: (state, getters) => (getters.loaded ? state.data.bgarants : []),
  pmts: (state, getters) => (getters.loaded ? state.data.pmts : []),
};

const mutations = {
  clearErrors(state) {
    state.error = null;
    state.updateError = null;
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
  update(state, error = null) {
    state.updatePending = false;
    state.updateError = error;
  },
  deleted(state, payload = null) {
    if (payload != null) {
      let pols;
      const [id, type] = payload;
      if (type === 'payment') pols = state.data.pymtPols;
      if (type === 'cancel') pols = state.data.cxlPols;
      const idx = pols.findIndex((pol) => pol.id === id);
      if (idx !== -1) {
        pols.splice(idx, 1);
      }
    }
    state.updatePending = false;
    state.updateError = null;
  },
};

function handleApiError(commit, error) {
  const { status, data } = error.response;
  let err = error;
  switch (status) {
    case 422:
      err = new ValidationError(status, data.message, data.errors);
      break;
    default:
      break;
  }
  commit('update', err);
  throw err;
}

const actions = {
  async fetchData({ commit }, forced = false) {
    commit('beforeLoading', forced);
    try {
      const response = await ApiService.get(`${endpoint}`);
      commit('data', response.data);
    } catch (error) {
      commit('error', error);
    }
  },
  async createCxlPol({ commit }, cxlPol) {
    commit('beforeUpdate');
    try {
      const response = await ApiService.post(`${endpoint}/cancel`, { ...cxlPol });
      commit('data', response.data);
      commit('update');
    } catch (error) {
      commit('update', error);
      throw handleApiError(commit, error);
    }
  },
  async updateCxlPol({ commit }, cxlPol) {
    commit('beforeUpdate');
    try {
      const response = await ApiService.put(`${endpoint}/cancel/${cxlPol.id}`, { ...cxlPol });
      commit('data', response.data);
      commit('update');
    } catch (error) {
      commit('update', error);
      throw handleApiError(commit, error);
    }
  },
  async duplicateCxlPol({ commit }, cxlPol) {
    commit('beforeUpdate');
    try {
      const response = await ApiService.post(`${endpoint}/cancel/${cxlPol.id}/duplicate`, { ...cxlPol });
      commit('data', response.data);
      commit('update');
    } catch (error) {
      commit('update', error);
      throw handleApiError(commit, error);
    }
  },
  async deleteCxlPol({ commit }, id) {
    commit('beforeUpdate');
    try {
      await ApiService.delete(`${endpoint}/cancel/${id}`);
      commit('deleted', [id, 'cancel']);
    } catch (error) {
      commit('update', error);
      throw handleApiError(commit, error);
    }
  },
  async createPymtPol({ commit }, pymtPol) {
    commit('beforeUpdate');
    try {
      const response = await ApiService.post(`${endpoint}/payment`, { ...pymtPol });
      commit('data', response.data);
      commit('update');
    } catch (error) {
      commit('update', error);
      throw handleApiError(commit, error);
    }
  },
  async updatePymtPol({ commit }, pymtPol) {
    commit('beforeUpdate');
    try {
      const response = await ApiService.put(`${endpoint}/payment/${pymtPol.id}`, { ...pymtPol });
      commit('data', response.data);
      commit('update');
    } catch (error) {
      commit('update', error);
      throw handleApiError(commit, error);
    }
  },
  async duplicatePymtPol({ commit }, pymtPol) {
    commit('beforeUpdate');
    try {
      const response = await ApiService.post(`${endpoint}/payment/${pymtPol.id}/duplicate`, { ...pymtPol });
      commit('data', response.data);
      commit('update');
    } catch (error) {
      commit('update', error);
      throw handleApiError(commit, error);
    }
  },
  async deletePymtPol({ commit }, id) {
    commit('beforeUpdate');
    try {
      await ApiService.delete(`${endpoint}/payment/${id}`);
      commit('deleted', [id, 'payment']);
    } catch (error) {
      commit('update', error);
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
