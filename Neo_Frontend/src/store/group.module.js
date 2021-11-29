/* eslint-disable no-shadow */

// import Vue from 'vue';
import ApiService from '@/services/api.service';
import { apiEndpoint } from '@/shared';
import { HttpError, PMSError, ValidationError } from '@/errors';
import Vue from 'vue';

const endpoint = `${apiEndpoint}/admin/groups`;

const state = {
  group: null,
  pending: false,
  error: null,
  updatePending: false,
  updateError: null,
  hotelsPending: false,
};

const getters = {
  loaded: (state) => state.group != null,
  group: (state, getters) => (getters.loaded ? state.group : {}),
  hotels: (state, getters) => (getters.group.hotels || []),
  hasHotels: (state, getters) => (getters.hotels.length > 0),
};

// function updateHotelsCount(commit, { id, data }) {
//   commit('groups/modified', { id, data }, { root: true });
// }

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
    state.invalid = false;
  },
  beforeUpdate(state) {
    state.updateError = null;
    state.updatePending = true;
  },
  afterUpdate(state) {
    state.updatePending = false;
  },
  data(state, { data = null, error = null, update = false } = {}) {
    if (!update) {
      state.pending = false;
    } else {
      state.updatePending = false;
    }
    state.group = data;
    state.error = error;
  },
  beforeHotelsRequest(state) {
    state.hotelsPending = true;
  },
  afterHotelsRequest(state) {
    state.hotelsPending = false;
  },
  addHotel(state, hotel) {
    if (state.group.hotels == null) {
      state.group.hotels = [];
    }
    state.group.hotels.push(hotel);
    state.hotelsPending = false;
  },
  updateHotel(state, hotel) {
    const idx = state.group.hotels.findIndex(({ id }) => id === hotel.id);
    if (idx !== -1) {
      Vue.set(state.group.hotels, idx, hotel);
    }
    state.hotelsPending = false;
  },
  patchHotel(state, hotel) {
    const h = state.group.hotels.find(({ id }) => id === hotel.id);
    if (h != null) {
      const data = { ...hotel };
      delete data.id;
      Object.keys(data).forEach((k) => {
        h[k] = data[k];
      });
    }
    state.hotelsPending = false;
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

const actions = {
  async fetchData({ commit }, { id, force = false } = {}) {
    commit('beforeLoading', force);
    try {
      const { data } = await ApiService.get(`${endpoint}/${id}`);
      commit('data', { data });
    } catch (error) {
      commit('data', { error });
      throw handleApiError(commit, error);
    }
  },
  async getHotel({ commit, state }, id) {
    commit('beforeHotelsRequest');
    const gid = state.group.id;
    try {
      const { data: { hotel, data } } = await ApiService.get(`${endpoint}/${gid}/hotels/${id}`);
      commit('updateHotel', hotel);
      return data;
    } catch (error) {
      commit('afterHotelsRequest');
      handleApiError(commit, error);
      return null;
    }
  },
  async createHotel({ commit, state }, payload) {
    commit('beforeHotelsRequest');
    const gid = state.group.id;
    try {
      const { data: hotel } = await ApiService.post(`${endpoint}/${gid}/hotels`, payload);
      commit('addHotel', hotel);
    } catch (error) {
      commit('afterHotelsRequest');
      handleApiError(commit, error);
    }
  },
  async updateHotel({ commit, state }, data) {
    commit('beforeHotelsRequest');
    const gid = state.group.id;
    try {
      const { id } = data;
      let payload;
      if (data.logo == null || data.logo.upload == null) {
        payload = { ...data };
      } else {
        payload = new FormData();
        payload.appendFromObject(data);
      }
      delete payload.id;
      const { data: hotel } = await ApiService.post(`${endpoint}/${gid}/hotels/${id}`, payload);
      commit('updateHotel', hotel);
    } catch (error) {
      commit('afterHotelsRequest');
      handleApiError(commit, error);
    }
  },
  async toggleHotelStatus({ commit, state }, data) {
    commit('beforeHotelsRequest');
    const gid = state.group.id;
    try {
      const { id } = data;
      const payload = { ...data };
      delete payload.id;
      await ApiService.patch(`${endpoint}/${gid}/hotels/${id}`, payload);
      commit('patchHotel', data);
    } catch (error) {
      commit('afterHotelsRequest');
      handleApiError(commit, error);
    }
  },
  async importHotel({ commit, state }, id) {
    commit('beforeHotelsRequest');
    const gid = state.group.id;
    try {
      const { data: hotel } = await ApiService.post(`${endpoint}/${gid}/hotels/import/${id}`);
      commit('addHotel', hotel);
    } catch (error) {
      commit('afterHotelsRequest');
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
