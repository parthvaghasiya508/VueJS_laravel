import Vue from 'vue';
import VueMatomo from 'vue-matomo';
import { router } from '../router';

const siteId = process.env.VUE_APP_MATOMO_SITEID;

let matomo = null;

function matomoEnabled() {
  return siteId != null && siteId > 0;
}

if (matomoEnabled()) {
  Vue.use(VueMatomo, {
    host: 'https://matomo.cultuzz.com',
    siteId,
    trackerFileName: 'piwik',
    router,
    enableLinkTracking: true,
    requireConsent: false,
    trackInitialView: false,
    disableCookies: false,
    requireCookieConsent: false,
    enableHeartBeatTimer: false,
    heartBeatTimerInterval: 15,
    debug: false,
    userId: undefined,
    cookieDomain: undefined,
    domains: undefined,
    preInitActions: [],
  });
}

async function getMatomo() {
  if (matomo != null) return matomo;
  return new Promise((resolve, reject) => {
    const checkInterval = 50;
    const timeout = 3000;
    const waitStart = Date.now();

    const interval = setInterval(() => {
      if (Vue.prototype.$matomo) {
        clearInterval(interval);
        matomo = Vue.prototype.$matomo;
        setTimeout(() => matomo.trackPageView(), 1);
        resolve(matomo);
      }

      if (Date.now() >= waitStart + timeout) {
        clearInterval(interval);
        reject(new Error(`[matomo]: undefined after waiting for ${timeout}ms`));
      }
    }, checkInterval);
  });
}

const MatomoService = {
  async setUser(user) {
    if (!matomoEnabled()) return;
    try {
      (await getMatomo()).setUserId(user.email);
    } catch (error) {
      // eslint-disable-next-line no-console
      console.error(error);
    }
  },
  async deleteUser() {
    if (!matomoEnabled()) return;
    try {
      (await getMatomo()).resetUserId();
    } catch (error) {
      // eslint-disable-next-line no-console
      console.error(error);
    }
  },
};

export default MatomoService;
