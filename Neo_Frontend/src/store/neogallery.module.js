/* eslint-disable no-shadow */

import ApiService from '@/services/api.service';
import { apiEndpoint } from '@/shared';

const endpoint = `${apiEndpoint}/images`;

const state = {
  rooms: null,
  images: null,
  pending: false,
  error: null,
  pmsError: null,
  updating: false,
  updatingImage: null,
  updatingRoom: null,
};

const getters = {
  loaded: (state) => (state.rooms != null && state.images != null && !state.pending),
  rooms: (state) => (state.rooms != null ? state.rooms : []),
  images: (state) => (state.images != null ? state.images : []),
};

function clearUpdating(state) {
  state.updating = false;
  state.updatingImage = null;
  state.updatingRoom = null;
}

const mutations = {
  clearErrors() {
    // do nothing
  },
  beforeLoading(state) {
    state.pending = true;
    state.rooms = null;
    state.images = null;
  },
  data(state, { rooms = null, images = null, error = null } = {}) {
    state.pending = false;
    state.rooms = rooms;
    state.images = images;
    state.error = error;
    state.pmsError = error;
  },
  beforeUpdate(state, { id = null, pid = null } = {}) {
    state.updating = true;
    state.updatingImage = id;
    state.updatingRoom = pid;
  },
  updated(state) {
    clearUpdating(state);
  },
  imageName(state, { id, name }) {
    const image = state.images.find((i) => i.id === id);
    if (image) image.display_name = name;
    clearUpdating(state);
  },
  imageRooms(state, { id, rooms }) {
    const image = state.images.find((i) => i.id === id);
    if (!image) return;
    const old = [...image.rooms];
    state.rooms.forEach((room) => {
      const { pid } = room;
      const exists = room.images.includes(id);
      if (exists && old.includes(pid) && !rooms.includes(pid)) {
        // delete old
        const idx = room.images.findIndex((i) => i === id);
        room.images.splice(idx, 1);
      }
      if (!exists && rooms.includes(pid)) {
        // add new
        room.images.push(id);
      }
    });
    image.rooms = rooms;
    clearUpdating(state);
  },
  imageCreate(state, image) {
    state.images.push(image);
    state.rooms
      .filter(({ pid }) => image.rooms.includes(pid))
      .forEach((room) => {
        room.images.push(image.id);
      });
    clearUpdating(state);
  },
  imageDelete(state, id) {
    state.rooms.forEach((room) => {
      const idx = room.images.findIndex((iid) => iid === id);
      if (idx !== -1) {
        room.images.splice(idx, 1);
      }
    });
    const idx = state.images.findIndex((i) => i.id === id);
    if (idx !== -1) {
      state.images.splice(idx, 1);
    }
    clearUpdating(state);
  },
  roomImages(state, { pid, images }) {
    const room = state.rooms.find((r) => r.pid === pid);
    if (!room) return;
    const old = [...room.images];
    state.images.forEach((image) => {
      const { id } = image;
      const exists = image.rooms.includes(pid);
      if (exists && old.includes(id) && !images.includes(id)) {
        // delete old
        const idx = image.rooms.findIndex((r) => r === pid);
        image.rooms.splice(idx, 1);
      }
      if (!exists && images.includes(id)) {
        image.rooms.push(pid);
      }
    });
    room.images = images;
    clearUpdating(state);
  },
};

const actions = {
  async fetchData({ commit }) {
    commit('beforeLoading');
    try {
      const { data } = await ApiService.get(`${endpoint}`);
      commit('data', data);
    } catch (error) {
      commit('data', { error });
    }
  },
  async uploadImage({ commit }, { file, room = null, progress = null }) {
    commit('beforeUpdate');
    try {
      const formData = new FormData();
      formData.append('image', file, file.name);
      if (room != null) {
        formData.append('room', room);
      }
      const config = {};
      if (progress) {
        config.onUploadProgress = progress;
      }
      // if (complete) {
      //   config.onUpload = complete;
      // }
      const { data: image } = await ApiService.post(`${endpoint}`, formData, config);
      commit('imageCreate', image);
    } catch (error) {
      // eslint-disable-next-line no-console
      console.error(error);
    }
  },
  async updateName({ commit }, { id, name }) {
    const obj = state.images.find((i) => i.id === id);
    if (obj == null) return;
    const original = obj.name;
    commit('imageName', { id, name });
    commit('beforeUpdate', { id });
    try {
      await ApiService.put(`${endpoint}/${id}`, { name });
      commit('updated');
    } catch (error) {
      commit('imageName', { id, name: original });
    }
  },
  async updateRooms({ commit }, { id, rooms }) {
    const obj = state.images.find((i) => i.id === id);
    if (obj == null) return;
    const original = [...obj.rooms];
    commit('imageRooms', { id, rooms });
    commit('beforeUpdate', { id });
    try {
      await ApiService.put(`${endpoint}/${id}`, { rooms });
      commit('updated');
    } catch (error) {
      commit('imageRooms', { id, rooms: original });
    }
  },
  async reorderImages({ commit }, { pid, images }) {
    const room = state.rooms.find((r) => r.pid === pid);
    if (room == null) return;
    const original = [...room.images];
    commit('roomImages', { pid, images });
    commit('beforeUpdate', { pid });
    try {
      await ApiService.patch(`${endpoint}/${pid}`, { images });
      commit('updated');
    } catch (error) {
      commit('roomImages', { pid, images: original });
    }
  },
  async deleteImage({ commit }, id) {
    commit('beforeUpdate', { id });
    try {
      await ApiService.delete(`${endpoint}/${id}`);
      commit('imageDelete', id);
    } catch (error) {
      // commit('mediaError', error);
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
