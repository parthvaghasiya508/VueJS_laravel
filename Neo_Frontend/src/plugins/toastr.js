import Vue from 'vue';
import VueToastr from 'vue-toastr';

Vue.use(VueToastr, {
  defaultPosition: 'toast-top-center',
  defaultTimeout: 5000,
  defaultProgressBar: true,
  defaultCloseOnHover: true,
});
