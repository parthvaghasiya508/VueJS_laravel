/* eslint-disable no-shadow */

import ApiService from '@/services/api.service';
import { apiEndpoint } from '@/shared';
import { HttpError, PMSError, ValidationError } from '@/errors';

const endpoint = `${apiEndpoint}/roles`;

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

const state = {
  roles: null,
  pending: false,
  error: null,
  updatePending: false,
  updateError: null,
};

const getters = {
  loaded: (state) => !state.pending && state.roles != null,
  roles: (state, getters) => (getters.loaded ? state.roles : []),
  noRoles: (state, getters) => getters.loaded && !getters.roles.length,
};

const mutations = {
  clearErrors(state) {
    state.error = null;
    state.updateError = null;
  },
  beforeLoading(state, initial = false) {
    if (initial) {
      state.group = null;
    }
    state.error = null;
    state.pending = true;
  },
  beforeUpdate(state) {
    state.updateError = null;
    state.updatePending = true;
  },
  roles(state, data) {
    state.pending = false;
    state.roles = data;
  },
  error(state, error) {
    state.pending = false;
    state.error = error;
  },
  created(state, { role = null, error = null } = {}) {
    state.updatePending = false;
    state.updateError = error;
    if (role != null) {
      state.roles.push(JSON.parse(JSON.stringify(role)));
    }
  },
  updated(state, { role = null, error = null } = {}) {
    if (role != null) {
      const obj = state.roles.find((p) => p.id === role.id);
      if (obj != null) {
        const data = JSON.parse(JSON.stringify(role));
        Object.keys(data).forEach((k) => {
          obj[k] = data[k];
        });
      }
    }
    state.updatePending = false;
    state.updateError = error;
  },
  deleted(state, { id = null, error = null } = {}) {
    if (id) {
      const idx = state.roles.findIndex((plan) => plan.id === id);
      if (idx !== -1) {
        state.roles.splice(idx, 1);
      }
    }
    state.updatePending = false;
    state.updateError = error;
  },
};

const actions = {
  async getRoles({ commit }) {
    try {
      const { data: roles } = await ApiService.get(`${endpoint}`);
      commit('roles', roles);
    } catch (error) {
      commit('error', error);
      handleApiError(commit, error);
    }
  },
  async createRole({ commit }, payload) {
    commit('beforeUpdate');
    try {
      const { data: role } = await ApiService.post(`${endpoint}`, payload);
      commit('created', { role });
    } catch (error) {
      commit('created', { error });
      handleApiError(commit, error);
    }
  },
  async updateRole({ commit }, role) {
    commit('beforeUpdate');
    const payload = { ...role };
    delete payload.id;
    try {
      await ApiService.put(`${endpoint}/${role.id}`, payload);
      commit('updated', { role });
    } catch (error) {
      commit('updated', { error });
      handleApiError(commit, error);
    }
  },
  async deleteRole({ commit }, id) {
    commit('beforeUpdate');
    try {
      await ApiService.delete(`${endpoint}/${id}`);
      commit('deleted', { id });
    } catch (error) {
      commit('deleted', { error });
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
