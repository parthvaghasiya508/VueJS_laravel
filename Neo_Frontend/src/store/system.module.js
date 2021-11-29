/* eslint-disable no-shadow */
import ApiService from '@/services/api.service';
import { apiEndpoint, systemList } from '@/shared';
import { HttpError, PMSError, ValidationError } from '@/errors';
import StorageService from '@/services/storage.service';

const endpoint = `${apiEndpoint}`;
const initialHotel = StorageService.getHotel();

const state = {
  data: null,
  pending: false,
  maintenanceIsOn: false,
  error: null,
  updatePending: false,
  updateError: null,
  cultuzzProduct: null,
  mapped: null,
  rateMapped: [],
  hotelKey: '',
  currentId: null,
  clientSecretValid: '',
  hostawayMapped: [],
  accountId: '',
};

const getters = {
  loaded: (state) => !state.pending && state.data != null,
  maintenanceIsOn: (state) => state.maintenanceIsOn,
  cultuzzProduct: (state, getters) => (getters.loaded ? state.cultuzzProduct : []),
  plans: (state, getters) => (getters.loaded ? state.data : []),
  mapped: (state, getters) => (getters.loaded ? state.mapped : []),
  connected: (state, getters) => (getters.loaded ? state.rateMapped : []),
  hotelKey: (state, getters) => (getters.loaded ? state.hotelKey : ''),
  accountId: (state, getters) => (getters.loaded ? state.accountId : ''),
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
    state.invalid = false;
  },
  beforeUpdate(state) {
    state.updateError = null;
    state.updatePending = true;
  },
  afterUpdate(state, error = null) {
    state.updatePending = false;
    state.updateError = error;
  },
  data(state, { data = null, error = null, update = false } = {}) {
    if (!update) {
      state.pending = false;
    } else {
      state.updatePending = false;
    }
    state.data = data;
    state.error = error;
  },
  enableMaintenanceMode: (state) => {
    state.maintenanceIsOn = true;
  },
  disableMaintenanceMode: (state) => {
    state.maintenanceIsOn = false;
  },
  cultuzzProduct(state, { data = null, error = null, update = false } = {}) {
    if (!update) {
      state.pending = false;
    } else {
      state.updatePending = false;
    }
    state.cultuzzProduct = data;
    state.error = error;
  },
  connect(state, mappings = []) {
    state.mapped = mappings;
    state.updatePending = false;
  },
  updateMapped(state, { selected }) {
    state.cultuzzProduct = state.cultuzzProduct.filter((p) => !selected.some(({ product }) => product.id === p.id));
    state.data = state.data.filter((p) => !selected.some(({ plan }) => plan.id === p.id));
  },
  toConnected(state, { selected }) {
    selected.map((s) => state.rateMapped.push({ plan: s.plan, product: s.product }));
  },
  rateMapping(state, { cultProduct }) {
    state.rateMapped = [];
    if (state.currentId === systemList.apaleo.id) {
      cultProduct.map((plan) => {
        state.mapped.map((m) => {
          if (m.cltzProductId === Number(plan.id)) {
            state.rateMapped.push({ plan: m, product: plan });
          }
          return m;
        });
        return plan;
      });
    } else if (state.currentId === systemList.hostaway.id) {
      cultProduct.map((plan) => {
        state.hostawayMapped.map((m) => {
          if (m.productId === Number(plan.id)) {
            state.mapped.map((m1) => {
              if (m.listingId === m1.id) {
                state.rateMapped.push({ plan: m1, product: plan });
              }
              return m1;
            });
          }
          return m;
        });
        return plan;
      });
    }
  },
  setHotelKey(state, newValue) {
    state.hotelKey = newValue;
  },
  setCurrentSystemId(state, newValue) {
    state.currentId = Number(newValue);
  },
  disconnect(state, index) {
    state.rateMapped.splice(index, 1);
  },
  setclientSecretValid(state, { valid }) {
    state.clientSecretValid = valid;
  },
  setHostawayMapped(state, mappedListing) {
    state.hostawayMapped = mappedListing;
  },
  setAccountId(state, data) {
    state.accountId = data;
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
  return err;
}

const actions = {
  async fetchData({ commit, dispatch }, { forced = false } = {}) {
    commit('beforeLoading', forced);
    try {
      if (state.currentId === systemList.apaleo.id) {
        let { data } = await ApiService.post(`${endpoint}/apaleo/rate-plans`, { object_id: initialHotel });
        const mappings = data.ratePlans.filter((e) => ('cltzProductId' in e));
        commit('connect', mappings);
        data = data.ratePlans.filter((e) => !('cltzProductId' in e));
        commit('data', { data });
      } else if (state.currentId === systemList.hostaway.id) {
        let { data: { result: mappings } } = await ApiService.get(`${endpoint}/hostaway/get-product-mappings`);
        commit('setHostawayMapped', mappings);
        const { data: { result: data } } = await ApiService.get(`${endpoint}/hostaway/get-listing`);
        let notMapped = data;
        if (mappings.length > 0) {
          notMapped = data.filter((d) => !mappings.some((m) => m.listingId === d.id));
          mappings = data.filter((d) => mappings.some((m) => m.listingId === d.id));
        }
        commit('data', { data: notMapped });
        commit('connect', mappings);
      }
      dispatch('getCultuzzProduct');
    } catch (error) {
      commit('data', { error });
      throw handleApiError(commit, error);
    }
  },
  async getCultuzzProduct({ commit }, forced = false) {
    commit('beforeLoading', forced);
    try {
      const { data } = await ApiService.get(`${endpoint}/plans/roomTypesWithRatePlan`);
      let newData = data.plans;
      newData = newData.map((p) => {
        data.rooms.map((room) => {
          if (room.pid === p.room) {
            // eslint-disable-next-line no-param-reassign
            p.roomType = room.text;
          }
          return room;
        });
        return p;
      });
      if (state.currentId === systemList.apaleo.id) {
        const cultProduct = newData.filter((p) => state.mapped.some((m) => m.cltzProductId === Number(p.id)));
        commit('rateMapping', { cultProduct });
        const product = newData.filter((p) => !state.mapped.some((m) => m.cltzProductId === Number(p.id)));
        commit('cultuzzProduct', { data: product });
      } else if (state.currentId === systemList.hostaway.id) {
        const cultProduct = newData.filter((p) => state.hostawayMapped.some(
          (m) => m.productId === Number(p.id),
        ));
        commit('rateMapping', { cultProduct });
        const product = newData.filter((p) => !state.hostawayMapped.some((m) => m.productId === Number(p.id)));
        commit('cultuzzProduct', { data: product });
      }
    } catch (error) {
      commit('cultuzzProduct', { error });
      handleApiError(error);
    }
  },
  async connectRatePlans({ commit, dispatch }, { propertyId = null, forced = true }) {
    commit('beforeLoading', forced);
    const selected = state.rateMapped;
    // console.log(selected);
    try {
      if (state.currentId === systemList.apaleo.id) {
        await ApiService.put(`${endpoint}/apaleo/product-map`, { selected, object_id: initialHotel, property_id: propertyId });
      } else if (state.currentId === systemList.hostaway.id) {
        await ApiService.put(`${endpoint}/hostaway/product-map`, { selected });
      }
      dispatch('fetchData');
      commit('afterUpdate');
    } catch (error) {
      commit('data', { error });
      throw handleApiError(commit, error);
    }
  },
  async disconnectRatePlan({ commit, dispatch }, {
    index, propertyId = null, forced = true,
  }) {
    commit('disconnect', index);
    commit('beforeLoading', forced);
    dispatch('connectRatePlans', { propertyId });
    commit('afterUpdate');
  },
  async setHotelKey({ commit, dispatch }, newValue) {
    commit('setHotelKey', newValue);
    dispatch('objectMap');
  },
  async setCurrentSystemId({ commit }, newValue) {
    commit('setCurrentSystemId', newValue);
  },
  async objectMap({ dispatch }) {
    try {
      await ApiService.post(`${endpoint}/apaleo/object-map`, { apaleoHotelKey: state.hotelKey, object_id: initialHotel });
      dispatch('getObjectMap');
    } catch (error) {
      handleApiError(error);
    }
  },
  async getObjectMap({ commit }) {
    try {
      const objectId = initialHotel;
      const { data } = await ApiService.get(`${endpoint}/apaleo/object-map/${objectId}`);
      commit('setHotelKey', data);
    } catch (error) {
      commit('data', { error });
      throw handleApiError(commit, error);
    }
  },
  async getConnectedAccountId({ commit }) {
    try {
      let { data } = await ApiService.get(`${endpoint}/hostaway/get-mapped-accountId`);
      data = data.result.accountId;
      commit('setAccountId', data);
    } catch (error) {
      commit('data', { error });
      throw handleApiError(commit, error);
    }
  },
  async hostawaySetClientSecret({ commit }, hostawayAccountID) {
    try {
      commit('beforeUpdate');
      const { data } = await ApiService.post(`${apiEndpoint}/hostaway/client-secret`, { object_id: initialHotel, account_id: hostawayAccountID.hostawayAccountID, api_key: hostawayAccountID.hostawayAPIKey });
      const valid = data.success;
      commit('setclientSecretValid', { valid });
      commit('afterUpdate');
    } catch (error) {
      handleApiError(error);
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
