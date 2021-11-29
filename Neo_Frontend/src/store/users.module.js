/* eslint-disable no-shadow */

import ApiService from '@/services/api.service';
import { apiEndpoint } from '@/shared';
import Vue from 'vue';
import { HttpError, PMSError, ValidationError } from '@/errors';

const endpoint = `${apiEndpoint}/users`;

function handleApiError(commit, error) {
  const { status, data } = error.response;
  let err = error;
  let cmt = false;
  switch (status) {
    case 400: // BadRequest
      err = new HttpError(status, data.message);
      break;
    case 409: // PMSError
      err = new PMSError(status, data.message);
      break;
    case 422:
      err = new ValidationError(status, data.message, data.errors);
      cmt = true;
      break;
    case 500:
      err = new Error(data.message);
      break;
    default:
      break;
  }
  if (cmt) {
    commit('updateError', err);
  } else {
    throw err;
  }
}
const state = {
  users: null,
  pending: false,
  updatePending: false,
  error: null,
  updateError: null,
};

const getters = {
  loaded: (state) => !state.pending && state.users != null,
  users: (state, getters) => (getters.loaded ? state.users : []),
  noUsers: (state, getters) => getters.loaded && !getters.users.length,
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
  update(state, error = null) {
    state.updatePending = false;
    state.updateError = error;
  },
  beforeUpdate(state) {
    state.updateError = null;
    state.updatePending = true;
  },
  afterUpdate(state) {
    state.updatePending = false;
  },
  users(state, users) {
    state.pending = false;
    state.users = users;
  },
  updateError(state, error) {
    state.updateError = error;
  },
  created(state, user) {
    if (state.users == null) return;
    state.users.push(JSON.parse(JSON.stringify(user)));
    state.updatePending = false;
  },
  updated(state, user) {
    const idx = state.users.findIndex((u) => u.id === user.id);
    if (idx !== -1) {
      Vue.set(state.users, idx, JSON.parse(JSON.stringify(user)));
    }
    state.updatePending = false;
  },
  deleted(state, id) {
    const idx = state.users.findIndex((user) => user.id === id);
    if (idx !== -1) {
      state.users.splice(idx, 1);
    }
    state.updatePending = false;
  },
};

const actions = {
  async getUsers({ commit }) {
    try {
      const { data: users } = await ApiService.get(`${endpoint}`);
      commit('users', users);
    } catch (error) {
      commit('error', error);
    }
  },
  async createUser({ commit }, data) {
    commit('beforeUpdate');
    try {
      const payload = new FormData();
      payload.appendFromObject(data);
      const { data: user } = await ApiService.post(`${endpoint}`, payload);
      commit('created', user);
      if (user.groups && user.groups.length) {
        user.groups.forEach((group) => {
          if (group.group_owner === user.id) {
            commit('groups/updateOwner', { user, group }, { root: true });
          }
        });
      }
      commit('afterUpdate');
      return true;
    } catch (error) {
      commit('afterUpdate');
      handleApiError(commit, error);
      return false;
    }
  },
  async updateUser({ commit }, data) {
    commit('beforeUpdate');
    try {
      let payload;
      if (data.avatar == null || data.avatar.upload == null) {
        payload = {
          ...data,
          _method: 'put',
        };
      } else {
        payload = new FormData();
        payload.append('_method', 'put');
        payload.appendFromObject(data);
      }
      const { data: user } = await ApiService.post(`${endpoint}/${data.id}`, payload);
      commit('updated', user);
      return true;
    } catch (error) {
      commit('afterUpdate');
      handleApiError(commit, error);
      return false;
    }
  },
  async deleteUser({ commit }, id) {
    try {
      const response = await ApiService.delete(`${endpoint}/${id}`);
      commit('deleted', id);
      return response.data.result;
    } catch (error) {
      handleApiError(commit, error);
      return false;
    }
  },
  async createInvite({ commit }, data) {
    commit('beforeUpdate');
    try {
      const { data: user } = await ApiService.post(`${endpoint}/invite`, data);
      commit('created', user);
      return true;
    } catch (error) {
      commit('afterUpdate');
      handleApiError(commit, error);
      return false;
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
