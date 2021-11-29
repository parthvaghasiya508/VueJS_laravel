/* eslint-disable no-shadow */

import Vue from 'vue';
import ApiService from '@/services/api.service';
import { apiEndpoint, defaultSettingColors, defaultProjectId } from '@/shared';
import { HttpError, PMSError, ValidationError } from '@/errors';
import StorageService from '@/services/storage.service';

const endpoint = `${apiEndpoint}/channels`;

const state = {
  data: null,
  pending: false,
  pmsError: null,
  error: null,
  updatePending: false,
  updateError: null,
  colors: null,
  email: null,
  languages: null,
  settingsHotelId: null,
};

const getters = {
  loaded: (state) => !state.pending && state.data != null,
  channel: (state, getters) => (getters.loaded ? state.data.channel : {}),
  ctypes: (state, getters) => (getters.loaded ? state.data.ctypes : []),
  cplans: (state, getters) => (getters.loaded ? state.data.cplans : []),
  rooms: (state, getters) => (getters.loaded ? state.data.rooms : []),
  plans: (state, getters) => (getters.loaded ? state.data.plans : []),
  mapped: (state, getters) => (getters.loaded ? state.data.mapped : []),
  colors: (state, getters) => (getters.loaded ? state.colors : []),
  email: (state, getters) => (getters.loaded ? state.email : []),
  languages: (state, getters) => (getters.loaded ? state.languages : []),
  settingsHotelId: (state) => state.settingsHotelId || null,
};

function updateMappedCount(state) {
  state.data.channel.count = Array.isArray(state.data.mapped)
    ? state.data.mapped.length
    : Object.keys(state.data.mapped).length;
}

function updateChannelInList(commit, channel) {
  commit('channels/modified', { channel }, { root: true });
}

function updateChannelMappingsInList(commit, channel, rooms) {
  commit('channels/mappings', { channel, rooms }, { root: true });
}

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
    state.pmsError = error;
  },
  connect(state, list = []) {
    if (Array.isArray(state.data.mapped)) Vue.set(state.data, 'mapped', {});
    list.forEach(({
      uniq, plan, mode, cplan: { id, typeid },
    }) => {
      Vue.set(state.data.mapped, plan.id, {
        id, typeid, uniq, mode,
      });
    });
    updateMappedCount(state);
    state.updatePending = false;
  },
  disconnect(state, list = []) {
    list.forEach(({ plan: { id } }) => {
      Vue.delete(state.data.mapped, id);
    });
    updateMappedCount(state);
    state.updatePending = false;
  },
  connectionUpdate(state, list) {
    list.forEach((m) => {
      Vue.set(state.data.mapped, m.plan.id, m);
    });
    updateMappedCount(state);
    state.updatePending = false;
  },
  mappingsUpdated(state, { rooms = null, error = null } = {}) {
    state.updatePending = false;
    state.updateError = error;
    if (rooms != null) {
      state.data.mapped = rooms.filter(({ inv }) => inv).map(({ id }) => id);
    }
    updateMappedCount(state);
  },
  promoCreated(state, { contract, plans }) {
    state.updatePending = false;
    state.data.channel.contractor.codes.push(contract);
    state.data.plans.push(...plans);
  },
  promoUpdated(state, { contract, discount }) {
    state.updatePending = false;
    const idx = state.data.channel.contractor.codes.findIndex(({ id }) => id === contract.id);
    if (idx >= 0) {
      Vue.set(state.data.channel.contractor.codes, idx, contract);
    }
    state.data.plans.forEach((plan) => {
      if (plan.promo !== contract.code) return;
      /* eslint-disable no-param-reassign */
      if (contract.mode === 'promo') {
        plan.price.stdcalc.reduction = { ...discount };
      }
      plan.validity.from = contract.from;
      plan.validity.until = contract.until;
      /* eslint-enable no-param-reassign */
    });
  },
  promoDeleted(state, { promoId, ids }) {
    state.updatePending = false;
    const idx = state.data.channel.contractor.codes.findIndex(({ id }) => id === promoId);
    if (idx >= 0) {
      state.data.channel.contractor.codes.splice(idx, 1);
    }
    state.data.plans = state.data.plans.filter(({ id }) => !ids.includes(id));
  },
  getHotel(state, data) {
    const [hotelData] = data;
    state.settingsHotelId = hotelData.id;
    state.pending = false;
  },
  getColor(state, { data }) {
    [state.colors] = data;
    state.pending = false;
  },
  colorUpdated(state, { data }) {
    state.colors = data;
    state.updatePending = false;
  },
  getLanguages(state, { data }) {
    state.languages = data;
    state.pending = false;
  },
  getEmail(state, data) {
    state.email = data;
    state.pending = false;
  },
  emailUpdated(state, data) {
    state.email = data;
    state.updatePending = false;
  },
  updateRatePlans(state) {
    state.updatePending = false;
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
  async fetchData({ commit }, { id, forced = false } = {}) {
    commit('beforeLoading', forced);
    try {
      const { data } = await ApiService.get(`${endpoint}/${id}`);
      commit('data', { data });
    } catch (error) {
      commit('data', { error });
      throw handleApiError(commit, error);
    }
  },
  async connectRatePlans({ commit, getters }, { id, list, update = false }) {
    commit('beforeUpdate');
    const rooms = list.map(({ cplan: { id, typeid }, plan: { id: rid }, mode }) => ({
      rid,
      id,
      typeid,
      mode,
      inv: true,
    }));
    try {
      await ApiService.put(`${endpoint}/${id}`, { rooms });
      if (!update) {
        commit('connect', list);
      } else {
        commit('connectionUpdate', list);
      }
      updateChannelInList(commit, getters.channel);
      updateChannelMappingsInList(commit, getters.channel, rooms);
    } catch (error) {
      commit('afterUpdate');
      throw handleApiError(commit, error);
    }
  },
  async disconnectRatePlan({ commit, getters }, { id, list }) {
    commit('beforeUpdate');
    const rooms = list.map(({ cplan: { id, typeid }, plan: { id: rid }, mode }) => ({
      rid,
      id,
      typeid,
      mode,
      inv: false,
    }));
    try {
      await ApiService.put(`${endpoint}/${id}`, { rooms });
      commit('disconnect', list);
      updateChannelInList(commit, getters.channel);
      updateChannelMappingsInList(commit, getters.channel, rooms);
    } catch (error) {
      commit('afterUpdate');
      throw handleApiError(commit, error);
    }
  },
  async updatePlanConnection({ dispatch }, { id, room, updates }) {
    const item = JSON.parse(JSON.stringify(room));
    Object.keys(updates).forEach((k) => {
      item[k] = updates[k];
    });
    await dispatch('connectRatePlans', { id, list: [item], update: true });
  },
  async channelMappings({ commit, getters }, { id, rooms }) {
    commit('beforeUpdate');
    try {
      await ApiService.put(`${endpoint}/${id}`, { rooms });
      commit('mappingsUpdated', { rooms });
      updateChannelInList(commit, getters.channel);
      updateChannelMappingsInList(commit, getters.channel, rooms);
    } catch (error) {
      commit('mappingsUpdated', { error });
      throw handleApiError(commit, error);
    }
  },
  async updateRatePlansData({ commit }, { id, payload }) {
    commit('beforeUpdate');
    try {
      const sendPayload = {
        id,
        mode: 'update',
        ...payload,
      };
      await ApiService.patch(`${endpoint}/${id}/rate`, sendPayload);
      commit('updateRatePlans');
    } catch (error) {
      commit('afterUpdate');
      throw handleApiError(commit, error);
    }
  },
  async updateChannelData({ commit, getters }, { id, payload }) {
    commit('beforeUpdate');
    try {
      const sendPayload = {
        mode: 'update',
        ...payload,
      };
      const { data } = await ApiService.patch(`${endpoint}/${id}`, sendPayload);
      commit('data', { data, update: true });
      updateChannelInList(commit, getters.channel);
    } catch (error) {
      // commit('mappingsUpdated', { error });
      commit('afterUpdate');
      throw handleApiError(commit, error);
    }
  },
  async createContract({ commit }, { id, promo }) {
    commit('beforeUpdate');
    try {
      const { mode } = promo;
      const payload = { ...promo };
      delete payload.mode;
      const { data: { contract, plans } } = await ApiService.post(`${endpoint}/${id}/${mode}`, payload);
      commit('promoCreated', { contract, plans });
    } catch (error) {
      const e = handleApiError(commit, error);
      commit('afterUpdate', (e instanceof ValidationError) ? e : null);
      throw e;
    }
  },
  async updateContract({ commit }, { id, promo }) {
    commit('beforeUpdate');
    try {
      const { id: promoId, mode } = promo;
      const payload = { ...promo };
      delete payload.id;
      delete payload.mode;
      const { data: { contract, discount } } = await ApiService.put(`${endpoint}/${id}/${mode}/${promoId}`, payload);
      commit('promoUpdated', { contract, discount });
    } catch (error) {
      commit('afterUpdate');
      throw handleApiError(commit, error);
    }
  },
  async deleteContract({ commit }, { id, promo }) {
    commit('beforeUpdate');
    const { id: promoId, mode } = promo;
    try {
      const { data: { ids } } = await ApiService.delete(`${endpoint}/${id}/${mode}/${promoId}`);
      commit('promoDeleted', { promoId, ids });
    } catch (error) {
      commit('afterUpdate');
      throw handleApiError(commit, error);
    }
  },
  async fetchHotel({ commit }, hotelId) {
    commit('beforeLoading');
    try {
      let { data } = await ApiService.get(`${endpoint}/${hotelId}/hotel`);
      if (!data.length) {
        const hotelData = {
          code: hotelId,
          defaultLanguage: StorageService.getLang(),
        };
        const createdData = await ApiService.post(`${endpoint}/hotel`, hotelData);
        data = [createdData.data];
      }
      commit('getHotel', data);
    } catch (error) {
      commit('data', { error });
    }
  },
  async fetchColor({ commit }, id) {
    commit('beforeLoading');
    try {
      const { data } = await ApiService.get(`${endpoint}/${id}/colors`);
      if (!data.length) {
        data.push({
          hotel: `/hotels/${id}`,
          hotel_languages: [],
          template_background_type: defaultSettingColors.template_background_type,
          header_background_type: defaultSettingColors.header_background_type,
          other_buttons_background_type: defaultSettingColors.other_buttons_background_type,
          product_background_type: defaultSettingColors.product_background_type,
          template_background_color: defaultSettingColors.template_background_color,
          template_background_color_top: defaultSettingColors.template_background_color_top,
          template_background_color_bottom: defaultSettingColors.template_background_color_bottom,
          header_background_color: defaultSettingColors.header_background_color,
          header_background_color_top: defaultSettingColors.header_background_color_top,
          header_background_color_bottom: defaultSettingColors.header_background_color_bottom,
          product_background_color: defaultSettingColors.product_background_color,
          product_background_color_top: defaultSettingColors.product_background_color_top,
          product_background_color_bottom: defaultSettingColors.product_background_color_bottom,
          input_background_color: defaultSettingColors.input_background_color,
          search_button_color: defaultSettingColors.search_button_color,
          search_button_background_color: defaultSettingColors.search_button_background_color,
          search_button_background_hover_color: defaultSettingColors.search_button_background_hover_color,
          other_buttons_color: defaultSettingColors.other_buttons_color,
          other_buttons_background_color: defaultSettingColors.other_buttons_background_color,
          other_buttons_background_hover_color: defaultSettingColors.other_buttons_background_hover_color,
          other_buttons_background_color_top: defaultSettingColors.other_buttons_background_color_top,
          other_buttons_background_color_bottom: defaultSettingColors.other_buttons_background_color_bottom,
          other_buttons_background_hover_color_top: defaultSettingColors.other_buttons_background_hover_color_top,
          other_buttons_background_hover_color_bottom: defaultSettingColors.other_buttons_background_hover_color_bottom,
          language_block_color: defaultSettingColors.language_block_color,
          date_background_color: defaultSettingColors.date_background_color,
          date_color: defaultSettingColors.date_color,
          product_name_color: defaultSettingColors.product_name_color,
          meals_included_colour: defaultSettingColors.meals_included_colour,
          meals_excluded_colour: defaultSettingColors.meals_excluded_colour,
          product_note_color: defaultSettingColors.product_note_color,
          product_amount_color: defaultSettingColors.product_amount_color,
          product_name_font_style: defaultSettingColors.product_name_font_style,
          product_note_font_style: defaultSettingColors.product_note_font_style,
          product_amount_font_style: defaultSettingColors.product_amount_font_style,
          check_in_out_color: defaultSettingColors.check_in_out_color,
          title_bar_color: defaultSettingColors.title_bar_color,
          title_bar_background_color: defaultSettingColors.title_bar_background_color,
          input_color: defaultSettingColors.input_color,
          input_error_border_color: defaultSettingColors.input_error_border_color,
          input_success_border_color: defaultSettingColors.input_success_border_color,
          cvc_input_show: true,
          smart_calendar: true,
          calendar_prices: true,
          mandatory_phone: true,
          address_street: true,
          address_city: true,
          address_zip: true,
          address_country: true,
          currency_block: true,
          show_on_map: true,
          hotel_logo_show: true,
          promo_code_show: true,
          reservation_email_to_property: true,
          powered_by: true,
          language_block_show: true,
          address_show: true,
        });
      } else {
        data[0].template_background_type = data[0].template_background_type
          ? data[0].template_background_type
          : defaultSettingColors.template_background_type;
        data[0].header_background_type = data[0].header_background_type
          ? data[0].header_background_type
          : defaultSettingColors.header_background_type;
        data[0].other_buttons_background_type = data[0].other_buttons_background_type
          ? data[0].other_buttons_background_type
          : defaultSettingColors.other_buttons_background_type;
        data[0].product_background_type = data[0].product_background_type
          ? data[0].product_background_type
          : defaultSettingColors.product_background_type;
        data[0].template_background_color = data[0].template_background_color
          ? data[0].template_background_color
          : defaultSettingColors.template_background_color;
        data[0].template_background_color_top = data[0].template_background_color_top
          ? data[0].template_background_color_top
          : defaultSettingColors.template_background_color_top;
        data[0].template_background_color_bottom = data[0].template_background_color_bottom
          ? data[0].template_background_color_bottom
          : defaultSettingColors.template_background_color_bottom;
        data[0].header_background_color = data[0].header_background_color
          ? data[0].header_background_color
          : defaultSettingColors.header_background_color;
        data[0].header_background_color_top = data[0].header_background_color_top
          ? data[0].header_background_color_top
          : defaultSettingColors.header_background_color_top;
        data[0].header_background_color_bottom = data[0].header_background_color_bottom
          ? data[0].header_background_color_bottom
          : defaultSettingColors.header_background_color_bottom;
        data[0].product_background_color = data[0].product_background_color
          ? data[0].product_background_color
          : defaultSettingColors.product_background_color;
        data[0].product_background_color_top = data[0].product_background_color_top
          ? data[0].product_background_color_top
          : defaultSettingColors.product_background_color_top;
        data[0].product_background_color_bottom = data[0].product_background_color_bottom
          ? data[0].product_background_color_bottom
          : defaultSettingColors.product_background_color_bottom;
        data[0].input_background_color = data[0].input_background_color
          ? data[0].input_background_color
          : defaultSettingColors.input_background_color;
        data[0].search_button_color = data[0].search_button_color
          ? data[0].search_button_color
          : defaultSettingColors.search_button_color;
        data[0].search_button_background_color = data[0].search_button_background_color
          ? data[0].search_button_background_color
          : defaultSettingColors.search_button_background_color;
        data[0].search_button_background_hover_color = data[0].search_button_background_hover_color
          ? data[0].search_button_background_hover_color
          : defaultSettingColors.search_button_background_hover_color;
        data[0].other_buttons_color = data[0].other_buttons_color
          ? data[0].other_buttons_color
          : defaultSettingColors.other_buttons_color;
        data[0].other_buttons_background_color = data[0].other_buttons_background_color
          ? data[0].other_buttons_background_color
          : defaultSettingColors.other_buttons_background_color;
        data[0].other_buttons_background_hover_color = data[0].other_buttons_background_hover_color
          ? data[0].other_buttons_background_hover_color
          : defaultSettingColors.other_buttons_background_hover_color;
        data[0].other_buttons_background_color_top = data[0].other_buttons_background_color_top
          ? data[0].other_buttons_background_color_top
          : defaultSettingColors.other_buttons_background_color_top;
        data[0].other_buttons_background_color_bottom = data[0].other_buttons_background_color_bottom
          ? data[0].other_buttons_background_color_bottom
          : defaultSettingColors.other_buttons_background_color_bottom;
        data[0].other_buttons_background_hover_color_top = data[0].other_buttons_background_hover_color_top
          ? data[0].other_buttons_background_hover_color_top
          : defaultSettingColors.other_buttons_background_hover_color_top;
        data[0].other_buttons_background_hover_color_bottom = data[0].other_buttons_background_hover_color_bottom
          ? data[0].other_buttons_background_hover_color_bottom
          : defaultSettingColors.other_buttons_background_hover_color_bottom;
        data[0].language_block_color = data[0].language_block_color
          ? data[0].language_block_color
          : defaultSettingColors.language_block_color;
        data[0].date_background_color = data[0].date_background_color
          ? data[0].date_background_color
          : defaultSettingColors.date_background_color;
        data[0].date_color = data[0].date_color
          ? data[0].date_color
          : defaultSettingColors.date_color;
        data[0].product_name_color = data[0].product_name_color
          ? data[0].product_name_color
          : defaultSettingColors.product_name_color;
        data[0].meals_included_colour = data[0].meals_included_colour
          ? data[0].meals_included_colour
          : defaultSettingColors.meals_included_colour;
        data[0].meals_excluded_colour = data[0].meals_excluded_colour
          ? data[0].meals_excluded_colour
          : defaultSettingColors.meals_excluded_colour;
        data[0].product_note_color = data[0].product_note_color
          ? data[0].product_note_color
          : defaultSettingColors.product_note_color;
        data[0].product_amount_color = data[0].product_amount_color
          ? data[0].product_amount_color
          : defaultSettingColors.product_amount_color;
        data[0].product_name_font_style = data[0].product_name_font_style
          ? data[0].product_name_font_style
          : defaultSettingColors.product_name_font_style;
        data[0].product_note_font_style = data[0].product_note_font_style
          ? data[0].product_note_font_style
          : defaultSettingColors.product_note_font_style;
        data[0].product_amount_font_style = data[0].product_amount_font_style
          ? data[0].product_amount_font_style
          : defaultSettingColors.product_amount_font_style;
        data[0].check_in_out_color = data[0].check_in_out_color
          ? data[0].check_in_out_color
          : defaultSettingColors.check_in_out_color;
        data[0].title_bar_color = data[0].title_bar_color
          ? data[0].title_bar_color
          : defaultSettingColors.title_bar_color;
        data[0].title_bar_background_color = data[0].title_bar_background_color
          ? data[0].title_bar_background_color
          : defaultSettingColors.title_bar_background_color;
        data[0].input_color = data[0].input_color
          ? data[0].input_color
          : defaultSettingColors.input_color;
        data[0].input_error_border_color = data[0].input_error_border_color
          ? data[0].input_error_border_color
          : defaultSettingColors.input_error_border_color;
        data[0].input_success_border_color = data[0].input_success_border_color
          ? data[0].input_success_border_color
          : defaultSettingColors.input_success_border_color;
      }
      commit('getColor', { data });
    } catch (error) {
      commit('data', { error });
    }
  },
  async saveColor({ commit }, data) {
    commit('beforeUpdate');
    try {
      let colorCollection = data;
      if (!colorCollection.id) {
        colorCollection = await ApiService.post(`${endpoint}/colors`, colorCollection);
      } else {
        delete colorCollection.hotel;
        colorCollection = await ApiService.put(`${endpoint}/${colorCollection.id}/colors`, colorCollection);
      }
      commit('colorUpdated', colorCollection);
    } catch (error) {
      commit('afterUpdate');
      throw handleApiError(commit, error);
    }
  },
  async fetchLanguages({ commit }) {
    commit('beforeLoading');
    try {
      const { data } = await ApiService.get(`${endpoint}/languages`);
      commit('getLanguages', { data });
    } catch (error) {
      commit('data', { error });
    }
  },
  async fetchEmail({ commit }, id) {
    commit('beforeLoading');
    try {
      const { data } = await ApiService.get(`${endpoint}/${id}/email`);
      let emailData = {};
      const pickupEmailItem = (langId) => {
        const result = data.filter((e) => e.language.id === langId);
        const email = {
          introduction: '',
          body: '',
          signature: '',
          language: `/languages/${langId}`,
          project: `/projects/${defaultProjectId}`,
          hotel: `/hotels/${state.settingsHotelId}`,
        };
        return result.length ? result[0] : email;
      };
      state.languages.forEach((l) => {
        emailData = { ...emailData, [l.iso]: pickupEmailItem(l.id) };
      });
      commit('getEmail', emailData);
    } catch (error) {
      commit('data', { error });
    }
  },
  async saveEmail({ commit }, data) {
    commit('beforeUpdate');
    const promises = [];
    state.languages.forEach((l) => {
      const email = data[l.iso];
      if (email.id) {
        delete email.language;
        delete email.hotel;
        delete email.project;
        promises.push(ApiService.put(`${endpoint}/${email.id}/email`, email));
      } else if (email.introduction) {
        promises.push(ApiService.post(`${endpoint}/email`, email));
      }
    });
    try {
      const data = await Promise.all(promises);
      let emailData = {};
      const pickupEmailItem = (langId) => {
        const result = data.filter((e) => e.data.language.id === langId);
        const email = {
          introduction: '',
          body: '',
          signature: '',
          language: `/languages/${langId}`,
          project: `/projects/${defaultProjectId}`,
          hotel: `/hotels/${state.settingsHotelId}`,
        };
        return result.length ? result[0].data : email;
      };
      state.languages.forEach((l) => {
        emailData = { ...emailData, [l.iso]: pickupEmailItem(l.id) };
      });
      commit('emailUpdated', emailData);
    } catch (error) {
      commit('afterUpdate');
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
