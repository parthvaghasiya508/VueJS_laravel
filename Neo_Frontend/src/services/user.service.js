import ApiService from '@/services/api.service';
import StorageService from '@/services/storage.service';
import {
  AuthError, TooManyAttemptsError, ValidationError, PMSError,
} from '@/errors';
import { appEndpoint, apiEndpoint, cookieEndpoint } from '@/shared';
import MatomoService from '@/services/matomo.service';

function handleApiError(error) {
  const { status, data } = error.response;
  const rlr = error.response.headers['x-ratelimit-reset'] || 0;
  switch (status) {
    case 401:
      // TODO: remove user from storage?
      return new AuthError(status, data.message);
    case 409: // PMSError
      return new PMSError(status, data.message);
    case 422:
      return new ValidationError(status, data.message, data.errors);
    case 429:
      return new TooManyAttemptsError(rlr ? data.message : data.errors.email[0], rlr);
    default:
      return error;
  }
}

const UserService = {
  async getCookie() {
    try {
      // ApiService.removeHeader();
      ApiService.setHotel();
      await ApiService.get(cookieEndpoint);
      ApiService.setHeader();
    } catch (error) {
      // FIXME
      // do nothing
    }
  },

  async login(payload) {
    try {
      // first, obtain session and csrf-cookie
      await this.getCookie();

      // now we can make login request
      await ApiService.post(`${appEndpoint}/login`, payload);

      // fetch user after successful login
      return await this.getUser();
    } catch (error) {
      throw handleApiError(error);
    }
  },

  async logout(forced = false) {
    if (!forced) {
      try {
        await ApiService.post(`${appEndpoint}/logout`);
      } catch (e) {
        // do nothing
      }
    }
    MatomoService.deleteUser();
    StorageService.removeUser();
    StorageService.removeHotels();
    ApiService.setHotel();
    // ApiService.removeHeader();
    ApiService.disableAuthErrorInterceptor();
  },

  async getUser(masterdata = false) {
    try {
      const response = await ApiService.get(`${apiEndpoint}/user${masterdata ? '/masterdata' : ''}`);
      const user = response.data;

      if (typeof user === 'object' && user.id != null) {
        MatomoService.setUser(user);
        StorageService.setUser(user);
        return user;
      }
      MatomoService.deleteUser();
      StorageService.removeUser();
      ApiService.setHotel();
      return null;
    } catch (error) {
      MatomoService.deleteUser();
      StorageService.removeUser();
      ApiService.setHotel();
      throw new AuthError(error.response.status, error.response.data.message);
    }
  },

  async getInfo() {
    try {
      const { data: info } = await ApiService.get(`${apiEndpoint}/info`);
      return info;
    } catch (error) {
      return null;
    }
  },

  async resendEmail() {
    try {
      await ApiService.post(`${appEndpoint}/email/resend`);
      return true;
    } catch (error) {
      throw handleApiError(error);
    }
  },

  async verifyEmail(id, hash, query) {
    try {
      // const queryParams = (new URLSearchParams(query)).toString();
      const url = `${appEndpoint}/email/verify/${id}/${hash}`;
      const response = await ApiService.post(url, query);
      let user;
      if (response.status === 204) {
        // already verified
        user = StorageService.getUser();
        user.email_verified = true;
      } else {
        user = response.data;
      }
      StorageService.setUser(user);
      return user;
    } catch (error) {
      throw handleApiError(error);
    }
  },

  async makeJoinHotel(id, hotelId, hash, query) {
    try {
      // const queryParams = (new URLSearchParams(query)).toString();
      const url = `${apiEndpoint}/join/users/${id}/hotels/${hotelId}/${hash}`;
      const response = await ApiService.post(url, query);
      let user;
      if (response.status === 204) {
        // already verified
        user = StorageService.getUser();
      } else {
        user = response.data;
      }
      StorageService.setUser(user);
      return user;
    } catch (error) {
      throw handleApiError(error);
    }
  },

  async updateProfile(data) {
    try {
      const url = `${apiEndpoint}/user/profile`;
      const response = await ApiService.post(url, data);
      const user = response.data;
      StorageService.setUser(user);
      return user;
    } catch (error) {
      throw handleApiError(error);
    }
  },
  async updateProfileData(data) {
    try {
      const url = `${apiEndpoint}/user`;
      const response = await ApiService.put(url, data);
      const user = response.data;
      StorageService.setUser(user);
      return user;
    } catch (error) {
      throw handleApiError(error);
    }
  },
  async sendResetPasswordEmail(email) {
    try {
      const url = `${appEndpoint}/password/email`;
      await ApiService.post(url, { email });
    } catch (error) {
      throw handleApiError(error);
    }
    return true;
  },

  async sendChangeEmail() {
    try {
      const email = 'emm';
      const url = `${appEndpoint}/email/send-email`;
      await ApiService.post(url, { email });
    } catch (error) {
      throw handleApiError(error);
    }
    return true;
  },

  async changeEmail(email, token, newEmail) {
    try {
      const url = `${appEndpoint}/email/update`;
      await ApiService.post(url, {
        email,
        token,
        newEmail,
      });
    } catch (error) {
      throw handleApiError(error);
    }
    return true;
  },

  async resetPassword(email, token, password) {
    try {
      const url = `${appEndpoint}/password/reset`;
      await ApiService.post(url, {
        email,
        token,
        password,
      });
    } catch (error) {
      throw handleApiError(error);
    }
    return true;
  },

  // eslint-disable-next-line camelcase
  async register(email, password, tos_agreed) {
    try {
      await this.getCookie();

      const url = `${appEndpoint}/register`;
      await ApiService.post(url, {
        email,
        password,
        tos_agreed,
      });
    } catch (error) {
      throw handleApiError(error);
    }
  },

  async setupStep(payload) {
    try {
      const url = `${apiEndpoint}/user/setup`;
      const { data } = await ApiService.patch(url, payload);
      return data;
    } catch (error) {
      throw handleApiError(error);
    }
  },
};

export default UserService;
