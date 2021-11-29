import Vue from 'vue';
import Vuex from 'vuex';

import auth from './auth.module';
import user from './user.module';
import data from './data.module';
import logs from './logs.module';
import calendar from './calendar.module';
import reservations from './reservations.module';
import roomtypes from './roomtypes.module';
import rateplans from './rateplans.module';
import mealplans from './mealplans.module';
import policies from './policies.module';
import nearby from './nearby.module';
import neogallery from './neogallery.module';
import channels from './channels.module';
import contacts from './contacts.module';
import channelshealth from './channelshealth.module';
import channel from './channel.module';
import facilities from './facilities.module';
import invoices from './invoices.module';
import description from './description.module';
import systems from './systems.module';
import system from './system.module';
import roles from './roles.module';
import groups from './groups.module';
import group from './group.module';
import users from './users.module';
import dashboard from './dashboard.module';
import payments from './payments.module';

Vue.use(Vuex);

const modules = {
  auth,
  user,
  data,
  logs,
  calendar,
  reservations,
  roomtypes,
  rateplans,
  mealplans,
  policies,
  nearby,
  neogallery,
  channels,
  contacts,
  channelshealth,
  channel,
  facilities,
  invoices,
  description,
  systems,
  system,
  roles,
  groups,
  group,
  users,
  payments,
  dashboard,
};

export default new Vuex.Store({
  strict: process.env.NODE_ENV !== 'production',
  state: {
    pageTitle: null,
    sidebar: false,
    centered: false,
    stretch: false,
  },
  getters: {
    pageTitle: (state) => state.pageTitle || 'Home',
    sidebar: (state) => (state.sidebar != null ? state.sidebar : true),
    centered: (state) => (state.centered != null ? state.centered : false),
    stretch: (state) => (state.stretch != null ? state.stretch : false),
  },
  mutations: {
    pageTitle(state, title) {
      state.pageTitle = title;
    },
    sidebar(state, enabled) {
      state.sidebar = enabled;
    },
    centered(state, enabled) {
      state.centered = enabled;
    },
    stretch(state, enabled) {
      state.stretch = enabled;
    },
  },
  actions: {
    clearErrors({ commit }) {
      Object.keys(modules)
        .forEach((module) => commit(`${module}/clearErrors`));
    },
  },
  modules,
});
