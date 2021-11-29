import axios from 'axios';

// workaround for cyclic imports
const storePromise = import('@/store');

const ApiService = {
  init() {
    let store;
    axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
    axios.defaults.withCredentials = true;
    axios.interceptors.response.use(
      (response) => response,
      async (error) => {
        if (store == null) {
          store = (await storePromise).default;
        }
        const code = error.request.status;
        switch (code) {
          case 401:
            // Unauthorized

            store.commit('user/sessionExpired');
            // do nothing for now
            // throw error;
            return null;

          case 419:
            // CSRF-token mismatch
            // refresh CSRF token
            await store.dispatch('auth/cookie');
            // retry original request
            return this.request(error.config);

          case 503:
            // Maintenance mode
            store.commit('system/enableMaintenanceMode');
            return null;

          default:
            throw error;
        }
      },
    );
  },
  setLang(lang) {
    axios.defaults.headers.common['Accept-Language'] = lang;
  },
  setHotel(hotel) {
    if (hotel != null) {
      axios.defaults.headers.common['X-Hotel-Id'] = hotel;
    } else {
      delete axios.defaults.headers.common['X-Hotel-Id'];
    }
  },
  setHeader() {
    // axios.defaults.headers.common.Authorization = `Bearer ${TokenService.getToken()}`;
  },
  removeHeader() {
    // axios.defaults.withCredentials = false;
    // axios.defaults.headers.common = {};
  },
  get(path, config = undefined) {
    return axios.get(path, config);
  },
  post(path, data, config = undefined) {
    return axios.post(path, data, config);
  },
  put(path, data, config = undefined) {
    return axios.put(path, data, config);
  },
  patch(path, data, config = undefined) {
    return axios.patch(path, data, config);
  },
  delete(path, config = undefined) {
    return axios.delete(path, config);
  },
  request(config) {
    return axios(config);
  },
  enableAuthErrorInterceptor() {},
  disableAuthErrorInterceptor() {},
};

export default ApiService;
