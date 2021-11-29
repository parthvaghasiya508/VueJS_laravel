/* eslint-disable no-shadow */

import { router } from '@/router';
import UserService from '@/services/user.service';
import StorageService from '@/services/storage.service';
import { ValidationError, AuthError, TooManyAttemptsError } from '@/errors';

const state = {
  authenticating: false,
  authErrorCode: 0,
  authError: '',
  validationError: null,
  rateLimitError: null,
  resetPasswordSent: false,
  changeEmailSent: false,
};

const getters = {
  authenticating: (state) => state.authenticating,
  authErrorCode: (state) => state.authErrorCode,
  authError: (state) => state.authError,
  validationError: (state) => state.validationError,
  rateLimitError: (state) => state.rateLimitError,
  resetPasswordSent: (state) => state.resetPasswordSent,
  changeEmailSent: (state) => state.changeEmailSent,
};

const mutations = {
  clearErrors(state) {
    state.authError = '';
    state.authErrorCode = 0;
    state.validationError = null;
    state.rateLimitError = null;
  },
  loginRequest(state) {
    state.authenticating = true;
    state.authError = '';
    state.authErrorCode = 0;
    state.validationError = null;
    state.rateLimitError = null;
  },
  loginSuccess(state) {
    state.authenticating = false;
  },
  loginAuthError(state, { code, message }) {
    state.authenticating = false;
    state.authError = message;
    state.authErrorCode = code;
  },
  validationError(state, error) {
    state.authenticating = false;
    state.validationError = error;
  },
  rateLimitError(state, error) {
    state.authenticating = false;
    state.rateLimitError = error;
  },
  sendPasswordReset(state) {
    state.authenticating = true;
    state.resetPasswordSent = false;
    state.rateLimitError = null;
  },
  sendEmailChange(state) {
    state.authenticating = true;
    state.changeEmailSent = false;
    state.rateLimitError = null;
  },
  resetPasswordSent(state) {
    state.authenticating = false;
    state.resetPasswordSent = true;
  },
  changeEmailSent(state) {
    state.authenticating = false;
    state.changeEmailSent = true;
  },
  resetPasswordRequest(state) {
    state.authenticating = true;
    state.validationError = null;
    state.rateLimitError = null;
  },
  changeEmailRequest(state) {
    state.authenticating = true;
    state.validationError = null;
    state.rateLimitError = null;
  },
  resetPasswordComplete(state) {
    state.authenticating = false;
  },
  changeEmailComplete(state) {
    state.authenticating = false;
  },
  registerRequest(state) {
    state.authenticating = true;
    state.validationError = null;
    state.rateLimitError = null;
  },
  registerSuccess(state) {
    state.authenticating = false;
  },
};

function handleError(commit, error) {
  if (error instanceof AuthError) {
    commit('loginAuthError', {
      code: error.errorCode,
      message: error.message,
    });
  }
  if (error instanceof ValidationError) {
    commit('validationError', error);
  }
  if (error instanceof TooManyAttemptsError) {
    commit('rateLimitError', error);
  }
}

const actions = {

  async cookie() {
    await UserService.getCookie();
  },

  async login({ commit, rootGetters }, payload) {
    const { syncUser = null } = payload;
    if (!syncUser) {
      commit('loginRequest');
      commit('user/user', null, { root: true });
    }

    try {
      const user = syncUser != null ? syncUser : await UserService.login(payload);
      await commit('loginSuccess', user);
      await commit('user/user', user, { root: true });
      const hotel = StorageService.getHotel();
      await commit('user/setHotel', hotel, { root: true });
      if (rootGetters['user/settings'].default_language) {
        await commit('user/setLang', rootGetters['user/settings'].default_language, { root: true });
      }
      let redirectTo = router.history.current.query.redirect || '/';
      if (!rootGetters['user/requiredFilled']) {
        redirectTo = { name: 'details' };
      }
      router.push(redirectTo);
    } catch (e) {
      handleError(commit, e);
    }
  },

  async logout({ commit, rootGetters }, { forced = false, stay = false, moveTo = { name: 'login' } }) {
    const toRoute = moveTo;
    if (stay) {
      const redirect = router.currentRoute.fullPath;
      if (redirect !== '/') {
        toRoute.query = { redirect };
      }
    }
    const otl = { ...rootGetters['user/userOTL'] };
    await UserService.logout(forced);
    if (otl.exit_url != null) {
      window.location = otl.exit_url;
      return;
    }
    commit('user/user', null, { root: true });
    commit('user/setHotel', null, { root: true });
    router.push(toRoute);
  },

  async sendResetPasswordEmail({ commit }, { email }) {
    try {
      commit('sendPasswordReset');
      await UserService.sendResetPasswordEmail(email);
      commit('resetPasswordSent');
    } catch (e) {
      handleError(commit, e);
    }
  },

  async resetPassword({ commit, dispatch }, { email, token, password }) {
    try {
      commit('resetPasswordRequest');
      await UserService.resetPassword(email, token, password);
      commit('resetPasswordComplete');
      dispatch('user/getUser', { redirect: true }, { root: true });
    } catch (e) {
      handleError(commit, e);
    }
  },

  async sendChangeEmail({ commit }) {
    try {
      commit('sendEmailChange');
      await UserService.sendChangeEmail();
      commit('changeEmailSent');
    } catch (e) {
      handleError(commit, e);
    }
  },
  async changeEmail({ commit, dispatch }, { email, token, newEmail }) {
    try {
      commit('changeEmailRequest');
      await UserService.changeEmail(email, token, newEmail);
      commit('changeEmailComplete');
      dispatch('user/getUser', { redirect: true }, { root: true });
    } catch (e) {
      handleError(commit, e);
    }
  },
  // eslint-disable-next-line camelcase
  async register({ commit, dispatch }, { email, password, tos_agreed }) {
    try {
      commit('registerRequest');
      await UserService.register(email, password, tos_agreed);
      commit('registerSuccess');
      dispatch('user/getUser', { redirect: true }, { root: true });
    } catch (e) {
      handleError(commit, e);
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
