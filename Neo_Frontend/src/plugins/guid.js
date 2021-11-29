import Vue from 'vue';

Vue.use((vm) => {
  Object.defineProperties(vm.prototype, {
    $uid: {
      get() {
        // eslint-disable-next-line no-underscore-dangle
        return `${this.$options.name}-${this._uid}`;
      },
    },
  });
});
