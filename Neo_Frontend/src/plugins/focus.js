import Vue from 'vue';

Vue.directive('focus', {
  inserted(el, binding) {
    if (binding.arg === false) {
      return;
    }
    const tag = el.tagName.toLowerCase();
    if (['input', 'select', 'textarea'].indexOf(tag) !== -1) {
      Vue.nextTick(() => el.focus());
    } else {
      const ch = el.querySelector('input,select,textarea');
      if (ch != null) {
        Vue.nextTick(() => ch.focus());
      }
    }
  },
});
