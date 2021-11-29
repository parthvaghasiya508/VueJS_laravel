import Vue from 'vue';

import '@/extensions';

import ApiService from '@/services/api.service';
import { router } from './router';
import store from './store';
import i18n from './i18n';

import App from './App.vue';

import '@/plugins';
import '@/components';

import '@/styles/main.scss';

ApiService.init();

if (process.env.VUE_APP_ZAMMAD_CHAT_ENABLED === 'true'
    || process.env.VUE_APP_ZAMMAD_FEEDBACK_ENABLED === 'true') {
  const jqueryScriptLink = document.createElement('script');
  jqueryScriptLink.setAttribute('src', 'https://code.jquery.com/jquery-2.1.4.min.js');
  document.querySelector('body').append(jqueryScriptLink);
}

if (process.env.VUE_APP_ZAMMAD_FEEDBACK_ENABLED === 'true') {
  const zammadFormScriptLink = document.createElement('script');
  zammadFormScriptLink.setAttribute('src', 'https://zammad.cultuzz.com/assets/form/form.js');
  zammadFormScriptLink.setAttribute('id', 'zammad_form_script');
  document.querySelector('body').append(zammadFormScriptLink);
}

if (process.env.VUE_APP_STRIPE_ENABLED === 'true') {
  const stripeJs = document.createElement('script');
  stripeJs.src = 'https://js.stripe.com/v3/';
  stripeJs.async = true;
  document.querySelector('head').append(stripeJs);
}

const hotjarKey = process.env.VUE_APP_HOTJAR_ID;
if (hotjarKey) {
  const hotjarScriptLink = document.createElement('script');
  hotjarScriptLink.async = true;
  // eslint-disable-next-line no-underscore-dangle
  window._hjSettings = { hjid: hotjarKey, hjsv: 6 };
  window.hj = function hjInit(...a) { (window.hj.q = window.hj.q || []).push(a); };
  hotjarScriptLink.src = `https://static.hotjar.com/c/hotjar-${hotjarKey}.js?sv=6`;
  document.querySelector('body').append(hotjarScriptLink);
}

if (process.env.VUE_APP_ZAMMAD_CHAT_ENABLED === 'true') {
  const zammadChatScriptLink = document.createElement('script');
  zammadChatScriptLink.setAttribute('src', 'https://zammad.cultuzz.com/assets/chat/chat.js');
  document.querySelector('body').append(zammadChatScriptLink);
}

const recaptchaLink = document.createElement('script');
recaptchaLink.async = true;
recaptchaLink.defer = true;
recaptchaLink.src = 'https://www.google.com/recaptcha/api.js?onload=vueRecaptchaApiLoaded&render=explicit';
document.querySelector('head').append(recaptchaLink);

Vue.config.productionTip = false;

new Vue({
  i18n,
  router,
  store,
  render: (h) => h(App, { ref: 'App' }),
}).$mount('#app');
