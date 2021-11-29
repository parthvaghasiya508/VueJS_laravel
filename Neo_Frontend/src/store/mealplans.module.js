/* eslint-disable no-shadow */

import Vue from 'vue';
import ApiService from '@/services/api.service';
import { apiEndpoint } from '@/shared';
import { ValidationError } from '@/errors';

const endpoint = `${apiEndpoint}/mealplans`;

const state = {
  mealplans: null,
  typecodes: null,
  pending: false,
  error: null,
  pmsError: null,
  updatePending: false,
  updateError: null,
};

const getters = {
  loaded: (state) => !state.pending && state.mealplans != null,
  mealplans: (state, getters) => (getters.loaded ? state.mealplans : []),
  typecodes: (state, getters) => (getters.loaded ? state.typecodes : []),
  noMeals: (state, getters) => getters.loaded && !getters.mealplans.length,
};

const mutations = {
  clearErrors(state) {
    state.error = null;
    state.updateError = null;
  },
  beforeLoading(state, initial = false) {
    if (initial) {
      state.mealplans = null;
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
  data(state, { mealplans, typecodes }) {
    state.pending = false;
    state.mealplans = mealplans;
    state.typecodes = typecodes;
  },
  update(state, error = null) {
    state.updatePending = false;
    state.updateError = error;
  },
  created(state, meal) {
    state.mealplans.push(JSON.parse(JSON.stringify(meal)));
    state.updatePending = false;
    state.updateError = null;
  },
  modified(state, meal) {
    const idx = state.mealplans.findIndex(({ id }) => id === meal.id);
    if (idx !== -1) {
      Vue.set(state.mealplans, idx, JSON.parse(JSON.stringify(meal)));
    }
    state.updatePending = false;
    state.updateError = null;
  },
  deleted(state, id) {
    const idx = state.mealplans.findIndex((meal) => meal.id === id);
    if (idx !== -1) {
      state.mealplans.splice(idx, 1);
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
      const { data: { mealplans, typecodes } } = await ApiService.get(`${endpoint}`);
      commit('data', { mealplans, typecodes });
    } catch (error) {
      commit('error', error);
    }
  },
  async createMeal({ commit }, mealplan) {
    commit('beforeUpdate');
    try {
      const { data } = await ApiService.post(`${endpoint}`, { ...mealplan });
      commit('created', data);
    } catch (error) {
      commit('update', error);
      throw handleApiError(commit, error);
    }
  },
  async updateMeal({ commit }, mealplan) {
    commit('beforeUpdate');
    try {
      const { data: langs } = await ApiService.put(`${endpoint}/${mealplan.id}`, { ...mealplan });
      commit('modified', { ...mealplan, langs });
    } catch (error) {
      commit('update', error);
      throw handleApiError(commit, error);
    }
  },
  async duplicateMeal({ commit }, id) {
    commit('beforeUpdate');
    try {
      const { data: mealplan } = await ApiService.post(`${endpoint}/${id}`);
      commit('created', mealplan);
    } catch (error) {
      commit('update', error);
      throw handleApiError(commit, error);
    }
  },
  async deleteMeal({ commit }, id) {
    commit('beforeUpdate');
    try {
      await ApiService.delete(`${endpoint}/${id}`);
      commit('deleted', id);
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
