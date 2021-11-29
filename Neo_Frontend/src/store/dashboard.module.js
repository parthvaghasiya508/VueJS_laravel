/* eslint-disable no-shadow */
import ApiService from '@/services/api.service';
import { apiEndpoint } from '@/shared';
import { PMSError, ValidationError } from '@/errors';

// const reportApiEndpoint = `${apiEndpoint}/reports/recent/`;
const dashboardApiEndpoint = `${apiEndpoint}/reports/dashboard/`;
const settingApiEndpoint = `${apiEndpoint}/reports/settings/`;
const widgetApiEndpoint = `${apiEndpoint}/widget`;

const state = {
  dashboardData: {
    id: null,
    client_id: null,
    available_rooms: null,
    client_name: null,
    update_date: null,
    producing_channels: null,
    calendar_days: null,
    channels: null,
    ibe_searches: null,
    ibe_unique_visitors: null,
    ibe_gross_booking: null,
    ibe_performance: null,
    gross_bookings: null,
    cancellation_bookings: null,
    net_bookings: null,
    roomnights_to_sell: null,
    bookings_for_period: null,
    gross_booking_volume: null,
    cancellation_booking_volume: null,
    net_booking_volume: null,
    average_booking_value: null,
    gross_sold_roomnights: null,
    net_sold_roomnights: null,
    cancellation_roomnights: null,
    roomnights_per_booking: null,
    average_daily_room_rate: null,
    cancellation_rate: null,
    online_occupancy_rate: null,
    online_rev_par: null,
  },
  settingData: [],
  dashboardDatapending: false,
  settingDatapending: false,
  dashboardDataLoaded: false,
  settingDataLoaded: false,
  dashboardDataError: null,
  settingDataError: null,
  dashboardDataUpdatePending: false,
  settingDataUpdatePending: false,
  dashboardDataUpdateError: null,
  settingDataUpdateError: null,
  dashboardDataPending: false,
};

const getters = {
  dashboardData: (state) => state.dashboardData,
  settingData: (state) => state.settingData,
  dashboardDataLoaded: (state) => state.dashboardData != null,
  settingDataLoaded: (state) => state.settingData != null,
  dashboardDataPending: (state) => state.dashboardDataPending,
  settingDataPending: (state) => state.settingDataPending,
  dashboardDataError: (state) => state.dashboardDataError,
  settingDataError: (state) => state.settingDataError,
};

const mutations = {
  clearErrors(state) {
    state.dashboardDataError = null;
    state.dashboardDataUpdateError = null;
    state.settingDataError = null;
    state.settingDataUpdateError = null;
  },
  dashboardDataBeforeRequest(state, initial = false) {
    if (initial) state.dashboardData = null;

    state.dashboardDataPending = true;
  },
  settingDataBeforeRequest(state, initial = false) {
    if (initial) state.setttingData = null;

    state.settingDataPending = true;
  },
  dashboardDataFetched(state, dashboardData = {}) {
    state.dashboardData = dashboardData;
    state.dashboardDataPending = false;
  },
  settingDataFetched(state, settingData = []) {
    state.settingData = [...settingData];
    state.settingDataPending = false;
  },
  dashboardDataError(state, error) {
    state.dashboardDataPending = false;
    state.dashboardDataError = error;
  },
  settingDataError(state, error) {
    state.settingDataPending = false;
    state.settingDataError = error;
  },
  settingDataUpdated(state) {
    state.settingDataUpdatePending = false;
  },
  settingItemVisibleChanged(state, { id, visibleState }) {
    for (let i = 0; i < state.settingData.length; i += 1) {
      if (state.settingData[i].id === id) {
        state.settingData[i].visible = visibleState;
        break;
      }
    }
  },
  settingGroupActivatedAll(state, groupId) {
    for (let i = 0; i < state.settingData.length; i += 1) {
      if (state.settingData[i].group.id === groupId) state.settingData[i].visible = true;
    }
  },
  settingGroupDeActivatedAll(state, groupId) {
    for (let i = 0; i < state.settingData.length; i += 1) {
      if (state.settingData[i].group.id === groupId) state.settingData[i].visible = false;
    }
  },
  settingDataRestored(state, data) {
    state.settingData = [...data];
  },
  settingDataUpdatedFromMovedWidgets(state, { widgets, layout }) {
    for (let i = 0; i < widgets.length; i += 1) {
      for (let j = 0; j < state.settingData.length; j += 1) {
        if (widgets[i].groupId === state.settingData[j].group.id && widgets[i].widgetId === state.settingData[j].id) {
          state.settingData[j].position = `${(layout[i].y + 1).toString()}-${(layout[i].x + 1).toString()}`;
          break;
        }
      }
    }
  },
};

function handleApiError(commit, error) {
  const { status, data } = error.response;
  switch (status) {
    case 409: // PMSError
      throw new PMSError(status, data.message);
    case 422:
      throw new ValidationError(status, data.message, data.errors);
    default:
      throw error;
  }
}

const actions = {
  async dashboardDataFetch({ commit }) {
    commit('dashboardDataBeforeRequest', true);
    try {
      const { data } = await ApiService.get(`${dashboardApiEndpoint}`);
      commit('dashboardDataFetched', data);
    } catch (error) {
      commit('dashboardDataError');
      handleApiError(commit, error);
    }
  },
  async settingDataFetch({ commit }) {
    commit('settingDataBeforeRequest', true);
    try {
      const { data } = await ApiService.get(`${settingApiEndpoint}`);
      commit('settingDataFetched', data);
    } catch (error) {
      commit('settingDataError');
      handleApiError(commit, error);
    }
  },
  async settingDataUpdate({ commit }, payload) {
    commit('settingDataBeforeRequest');
    try {
      await ApiService.put(`${widgetApiEndpoint}`, payload);
      // commit('settingDataUpdated');
    } catch (error) {
      commit('settingDataError');
      handleApiError(commit, error);
    }
  },
  async updateWidgetPosition({ commit }, payload) {
    commit('settingDataBeforeRequest');
    try {
      await ApiService.put(`${widgetApiEndpoint}/update-position`, payload);
    } catch (error) {
      commit('settingDataError');
      handleApiError(commit, error);
    }
  },
  settingItemVisibleChange({ commit }, payload) {
    commit('settingItemVisibleChanged', payload);
  },
  settingGroupActivateAll({ commit }, groupId) {
    commit('settingGroupActivatedAll', groupId);
  },
  settingGroupDeActivateAll({ commit }, groupId) {
    commit('settingGroupDeActivatedAll', groupId);
  },
  settingDataRestore({ commit }, data) {
    commit('settingDataRestored', data);
  },
  settingDataUpdateFromMovedWidgets({ commit }, payload) {
    commit('settingDataUpdatedFromMovedWidgets', payload);
  },
  async updateWidgetVisiblity({ commit }, payload) {
    commit('settingDataBeforeRequest');
    try {
      await ApiService.put(`${widgetApiEndpoint}/update-visibility`, payload);
      commit('settingDataUpdated');
    } catch (error) {
      commit('settingDataError');
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
