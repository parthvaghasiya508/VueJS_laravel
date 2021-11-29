import Vue from 'vue';
import Vuelidate from 'vuelidate';

Vuelidate.extend({
  getters: {
    $state() {
      return true;
    },
  },
});
Vue.use(Vuelidate);
