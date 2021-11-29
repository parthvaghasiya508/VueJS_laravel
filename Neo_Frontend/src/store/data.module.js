/* eslint-disable no-shadow */

import ApiService from '@/services/api.service';
import { apiEndpoint } from '@/shared';

const state = {
  countries: null,
  states: null,
  currencies: null,
  pages: null,
  types: null,
  languages: null,
};

const getters = {
  countries: (state) => (state.countries != null ? state.countries : []),
  states: (state) => (state.states != null ? state.states : []),
  types: (state) => (state.types != null ? state.types : []),
  currencies: (state) => (state.currencies != null ? state.currencies : []),
  pages: (state) => (state.pages != null ? state.pages : []),
  loadedCurrencies: (state) => state.currencies != null,
  languages: (state) => (state.languages != null ? state.languages : []),
};

const mutations = {
  clearErrors() {
    // do nothing
  },
  countries(state, countries) {
    state.countries = countries;
  },
  states(state, states) {
    state.states = states;
  },
  types(state, types) {
    state.types = types;
  },
  currencies(state, currencies) {
    state.currencies = currencies;
  },
  pages(state, pages) {
    state.pages = pages;
  },
  languages(state, languages) {
    state.languages = languages;
  },
};

const actions = {
  async fetchCountries({ commit, getters }) {
    if (getters.countries.length) return;

    try {
      const response = await ApiService.get(`${apiEndpoint}/data/countries`);
      const data = response.data ? response.data.data : null;
      commit('countries', data);
    } catch (error) {
      // do nothing
    }
  },
  async fetchStates({ commit }, payload) {
    try {
      const response = await ApiService.get(`${apiEndpoint}/data/countries/${payload.country_id}/states`);
      const data = response.data ? response.data.data : null;
      commit('states', data);
    } catch (error) {
      // do nothing
    }
  },
  async fetchCurrencies({ commit, getters }) {
    if (getters.currencies.length) return;

    try {
      const response = await ApiService.get(`${apiEndpoint}/data/currencies`);
      commit('currencies', response.data);
    } catch (error) {
      // do nothing
    }
  },
  async fetchPropertyTypes({ commit, getters }) {
    if (getters.currencies.length) return;

    try {
      const response = await ApiService.get(`${apiEndpoint}/data/property-types`);
      commit('types', response.data);
    } catch (error) {
      // do nothing
    }
  },
  async fetchPages({ commit, getters }) {
    if (getters.pages.length) return;

    try {
      const response = await ApiService.get(`${apiEndpoint}/data/pages`);
      commit('pages', response.data);
    } catch (error) {
      // do nothing
    }
  },
  async fetchLanguages({ commit }) {
    try {
      const response = await ApiService.get(`${apiEndpoint}/data/languages`);
      commit('languages', response.data.data.result);
    } catch (error) {
      // do nothing
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
