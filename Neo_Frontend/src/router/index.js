import Vue from 'vue';
import VueRouter from 'vue-router';
import StorageService from '@/services/storage.service';
import i18n from '@/i18n';
import ApiService from '@/services/api.service';
import { translationToolUrl, translationToolProject } from '@/shared';


Vue.use(VueRouter);

const routes = [
  {
    path: '/',
    name: 'home',
    meta: {
      translationKey: 'home',
    },
  },
  {
    path: '/no-perms',
    name: 'noperms',
    component: () => import(/* webpackChunkName: "user" */ '@/views/User/NoPermissions'),
    meta: {
      // centered: true,
      sideBar: false,
      empty: true,
      translationKey: 'noPerms',
    },
  },
  {
    path: '/login/:uuid([a-z0-9]{8}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{12})?',
    name: 'login',
    meta: {
      title: 'Welcome',
      documentTitle: '*login.pageTitle',
      public: true,
      guestOnly: true,
      translationKey: 'login',
    },
    component: () => import(/* webpackChunkName: "auth" */ '@/views/Auth/Login.vue'),
  },
  {
    path: '/register',
    name: 'register',
    meta: {
      title: 'Sign Up',
      documentTitle: '*signup.title',
      sideBar: false,
      public: true,
      guestOnly: true,
      agent: false,
      translationKey: 'register',
    },
    component: () => import(/* webpackChunkName: "auth" */ '@/views/Auth/Register.vue'),
  },
  {
    path: '/password/email',
    name: 'password.email',
    meta: {
      title: 'Forgot Password',
      documentTitle: '*forgot-pwd.title',
      public: true,
      guestOnly: true,
      agent: false,
      translationKey: 'passwordEmail',
    },
    component: () => import(/* webpackChunkName: "auth" */ '@/views/Auth/ForgotPassword.vue'),
  },
  {
    path: '/password/change',
    name: 'password.change',
    meta: {
      title: 'Change Password',
      documentTitle: '*change-pwd.title',
      public: true,
      guestOnly: true,
      agent: false,
      translationKey: 'passwordChange',
    },
    component: () => import(/* webpackChunkName: "auth" */ '@/views/Auth/ChangePasswordSent.vue'),
  },
  {
    path: '/email/change',
    name: 'email.change',
    meta: {
      title: 'Change Email',
      documentTitle: '*change-email.title',
      public: true,
      guestOnly: true,
      agent: false,
      translationKey: 'emailChange',
    },
    component: () => import(/* webpackChunkName: "auth" */ '@/views/Auth/ChangeEmailRequestSent.vue'),
  },
  {
    path: '/email/update',
    name: 'email.update',
    meta: {
      title: 'Change Email',
      documentTitle: '*change-email.title',
      public: true,
      guestOnly: true,
      agent: false,
      translationKey: 'emailUpdate',
    },
    component: () => import(/* webpackChunkName: "auth" */ '@/views/Auth/ChangeEmail.vue'),
  },
  {
    path: '/password/reset',
    name: 'password.reset',
    meta: {
      title: 'Reset Password',
      documentTitle: '*reset-pwd.title',
      public: true,
      guestOnly: true,
      agent: false,
      translationKey: 'passwordReset',
    },
    component: () => import(/* webpackChunkName: "auth" */ '@/views/Auth/ResetPassword.vue'),
  },
  {
    path: '/email/verify/:id?/:hash?',
    name: 'verification.notice',
    meta: {
      sideBar: false,
      title: 'Verification',
      documentTitle: '*signup.verify.title',
      centered: true,
      agent: false,
      translationKey: 'verificationNotice',
    },
    component: () => import(/* webpackChunkName: "user" */ '@/views/User/VerifyEmailNotice.vue'),
  },
  {
    path: '/join/users/:id?/hotels/:hotel_id?/:hash?',
    name: 'users.hotel.join',
    meta: {
      title: 'Accept to join',
      centered: true,
      perms: false,
      translationKey: 'userHotelJoin',
    },
    component: () => import(/* webpackChunkName: "user" */ '@/views/User/JoinHotel.vue'),
  },
  {
    path: '/details',
    name: 'details',
    meta: {
      centered: true,
      translationKey: 'details',
    },
    component: () => import(/* webpackChunkName: "user" */ '@/views/User/Details.vue'),
  },
  {
    path: '/setup',
    name: 'setup',
    meta: {
      title: 'Setup Wizard',
      stretch: true,
      translationKey: 'setupWizard',
    },
    component: () => import(/* webpackChunkName: "user" */ '@/views/User/Setup.vue'),
  },
  {
    path: '/maintenance',
    name: 'maintenance',
    meta: {
      title: 'Maintenance',
      centered: true,
      public: true,
      maintenance: true,
      translationKey: 'maintenance',
    },
    component: () => import(/* webpackChunkName: "maintenance" */ '@/views/Maintenance.vue'),
  },
  {
    path: '/calendar',
    name: 'calendar',
    meta: {
      title: 'Calendar',
      documentTitle: '*pages.calendar.title',
      translationKey: 'calendar',
    },
    component: () => import(/* webpackChunkName: "calendar" */ '@/views/Calendar.vue'),
  },
  {
    path: '/invoices',
    name: 'invoices',
    meta: {
      title: 'Invoices',
      documentTitle: '*pages.invoices.title',
      agent: false,
      translationKey: 'invoices',
    },
    component: () => import(/* webpackChunkName: "invoices" */ '@/views/Invoices.vue'),
  },
  {
    path: '/profile',
    name: 'profile',
    meta: {
      title: 'Profile Settings',
      documentTitle: '*pages.profile.title',
      empty: true,
      agent: false,
      translationKey: 'profile',
    },
    component: () => import(/* webpackChunkName: "profile" */ '@/views/User/Profile.vue'),
  },
  {
    path: '/dashboard',
    name: 'dashboard',
    meta: {
      title: 'Dashboard',
      documentTitle: '*pages.dashboard.title',
      translationKey: 'dashboard',
    },
    component: () => import(/* webpackChunkName: "dashboard" */ '@/views/Dashboard.vue'),
  },
  {
    path: '/reservations',
    name: 'reservations',
    meta: {
      title: 'Reservations',
      documentTitle: '*pages.reservations.title',
      translationKey: 'reservations',
    },
    component: () => import(/* webpackChunkName: "reservations" */ '@/views/Reservations.vue'),
  },
  {
    path: '/legal/:page?',
    name: 'legal',
    meta: {
      agent: false,
      translationKey: 'legal',
    },
    component: () => import(/* webpackChunkName: "legal" */ '@/views/Legal.vue'),
  },
  {
    path: '/users',
    name: 'users',
    meta: {
      title: 'Users & Roles',
      documentTitle: '*pages.users.title',
      empty: true,
      translationKey: 'users',
    },
    component: () => import(/* webpackChunkName: "users" */ '@/views/Users.vue'),
  },
  {
    path: '/meal-plans',
    name: 'mealplans',
    meta: {
      title: 'Meal plans',
      documentTitle: '*pages.mealplans.title',
      translationKey: 'mealPlans',
    },
    component: () => import(/* webpackChunkName: "mealplans" */ '@/views/MealPlans.vue'),
  },
  {
    path: '/promotions',
    name: 'promotions',
    meta: {
      title: 'Promotions',
      documentTitle: '*pages.promotions.title',
      translationKey: 'promotions',
    },
    component: () => import(/* webpackChunkName: "promotions" */ '@/views/Promotions.vue'),
  },
  {
    path: '/contracts',
    name: 'contracts',
    meta: {
      title: 'Contracts',
      documentTitle: '*pages.contracts.title',
      translationKey: 'contracts',
    },
    component: () => import(/* webpackChunkName: "promotions" */ '@/views/Contracts.vue'),
  },
  {
    path: '/room-types',
    name: 'roomtypes',
    meta: {
      title: 'Room types',
      documentTitle: '*pages.roomtypes.title',
      translationKey: 'roomTypes',
    },
    component: () => import(/* webpackChunkName: "roomtypes" */ '@/views/RoomTypes.vue'),
  },
  {
    path: '/rate-plans',
    name: 'rateplans',
    meta: {
      title: 'Rate plans',
      documentTitle: '*pages.rateplans.title',
      translationKey: 'ratePlans',
    },
    component: () => import(/* webpackChunkName: "rateplans" */ '@/views/RatePlans.vue'),
  },
  {
    path: '/master-data',
    name: 'masterdata',
    meta: {
      title: 'Master Data',
      documentTitle: '*pages.masterdata.title',
      translationKey: 'masterData',
    },
    component: () => import(/* webpackChunkName: "masterdata" */ '@/views/MasterData.vue'),
  },
  {
    path: '/booking-status',
    name: 'booking',
    meta: {
      title: 'Booking Status',
      documentTitle: '*pages.booking.title',
      translationKey: 'booking',
    },
    component: () => import(/* webpackChunkName: "booking" */ '@/views/BookingStatus.vue'),
  },
  {
    path: '/contact-persons',
    name: 'contactpersons',
    meta: {
      title: 'Contact Persons',
      documentTitle: '*pages.contactpersons.title',
      translationKey: 'contactPersons',
    },
    component: () => import(/* webpackChunkName: "contactpersons" */ '@/views/ContactPersons.vue'),
  },
  {
    path: '/nearby',
    name: 'nearby',
    meta: {
      title: 'What\'s nearby',
      documentTitle: '*pages.nearby.title',
      translationKey: 'nearby',
    },
    component: () => import(/* webpackChunkName: "nearby" */ '@/views/NearBy.vue'),
  },
  {
    path: '/facilities',
    name: 'facilities',
    meta: {
      title: 'Facilities',
      documentTitle: '*pages.facilities.title',
      translationKey: 'facilities',
    },
    component: () => import(/* webpackChunkName: "facilities" */ '@/views/Facilities.vue'),
  },
  {
    path: '/policies',
    name: 'policies',
    meta: {
      title: 'Policies',
      documentTitle: '*pages.policies.title',
      translationKey: 'policies',
    },
    component: () => import(/* webpackChunkName: "policies" */ '@/views/Policies.vue'),
  },
  {
    path: '/photos',
    name: 'photos',
    meta: {
      title: 'Photos',
      documentTitle: '*pages.photos.title',
      translationKey: 'photos',
    },
    component: () => import(/* webpackChunkName: "photos" */ '@/views/Photos.vue'),
  },
  {
    path: '/channels',
    name: 'channels',
    meta: {
      title: 'Channels',
      documentTitle: '*pages.channels.title',
      translationKey: 'channels',
    },
    component: () => import(/* webpackChunkName: "channels" */ '@/views/Channels.vue'),
  },
  {
    path: '/channels/:id',
    name: 'channel',
    meta: {
      permission: 'channels',
      title: 'Channels',
      documentTitle: '*pages.channels.title',
      translationKey: 'channel',
    },
    component: () => import(/* webpackChunkName: "channels" */ '@/views/Channel.vue'),
  },
  {
    path: '/systems',
    name: 'systems',
    meta: {
      title: 'Systems',
      documentTitle: '*pages.systems.title',
      translationKey: 'systems',
    },
    component: () => import(/* webpackChunkName: "systems" */ '@/views/Systems.vue'),
  },
  {
    path: '/systems/:id',
    name: 'system',
    meta: {
      permission: 'systems',
      title: 'Systems',
      documentTitle: '*pages.systems.title',
      translationKey: 'system',
    },
    component: () => import(/* webpackChunkName: "systems" */ '@/views/System.vue'),
  },
  {
    path: '/hotels',
    name: 'hotels',
    meta: {
      title: 'Properties',
      documentTitle: '*pages.hotels.title',
      empty: true,
      translationKey: 'hotels',
    },
    component: () => import(/* webpackChunkName: "hotels" */ '@/views/Hotels.vue'),
  },
  {
    path: '/group',
    name: 'group',
    meta: {
      title: 'Property Group',
      documentTitle: '*pages.group.title',
      empty: true,
      translationKey: 'group',
    },
    component: () => import(/* webpackChunkName: "group" */ '@/views/Group.vue'),
  },
  {
    path: '/descriptions',
    name: 'description',
    meta: {
      title: 'Descriptions',
      documentTitle: '*pages.description.title',
      translationKey: 'description',
    },
    component: () => import(/* webpackChunkName: "descriptions" */ '@/views/Descriptions.vue'),
  },
  {
    path: '/emergencystop',
    name: 'emergencystop',
    meta: {
      title: 'Emergency Stop',
      documentTitle: '*pages.emergency.title',
    },
    component: () => import(/* webpackChunkName: "channels" */ '@/views/ChannelsHealth.vue'),
  },
  {
    path: '/outages',
    name: 'outages',
    meta: {
      title: 'Outages',
      documentTitle: '*pages.outages.title',
    },
    component: () => import(/* webpackChunkName: "channels" */ '@/views/ChannelsHealth.vue'),
  },
  {
    path: '/channelshealth',
    name: 'channelshealth',
    meta: {
      title: 'Channels Health',
      documentTitle: '*pages.channelshealth.title',
    },
    component: () => import(/* webpackChunkName: "channels" */ '@/views/ChannelsHealth.vue'),
  },
  {
    path: '/suppliershealth',
    name: 'suppliershealth',
    meta: {
      title: 'Suppliers Health',
      documentTitle: '*pages.suppliershealth.title',
    },
    component: () => import(/* webpackChunkName: "channels" */ '@/views/ChannelsHealth.vue'),
  },
  {
    path: '/admin/groups',
    name: 'admin-groups',
    meta: {
      title: 'Groups',
      documentTitle: '*pages.groups.title',
      admin: true,
      empty: true,
      translationKey: 'adminGroups',
    },
    component: () => import(/* webpackChunkName: "admin" */ '@/views/Admin/Groups.vue'),
  },
  {
    path: '/admin/groups/:id?',
    name: 'admin-group',
    meta: {
      title: '*pages.groups.manage.title',
      admin: true,
      empty: true,
      translationKey: 'adminGroup',
    },
    component: () => import(/* webpackChunkName: "admin" */ '@/views/Admin/Group.vue'),
  },
  {
    path: '/payments',
    name: 'payments',
    meta: {
      title: 'Payments',
      documentTitle: '*pages.payments.title',
      empty: true,
      translationKey: 'payments',
    },
    component: () => import(/* webpackChunkName: "payments" */ '@/views/Payments.vue'),
  },
];

const router = new VueRouter({
  mode: 'history',
  base: process.env.BASE_URL,
  linkExactActiveClass: 'exact-active',
  linkActiveClass: 'active',
  routes,
});

export default router;

export { router };

let initialUserCheckDone = false;

let $store = null;
const initStore = () => {
  Vue.nextTick(() => {
    // save $store for future use
    $store = router.app.$store;
    $store.commit('system/disableMaintenanceMode');
  });
};
const checkLoggedIn = () => new Promise((resolve) => {
  Vue.nextTick(async () => {
    await Promise.allSettled([
      $store.dispatch('user/getUser'),
      $store.dispatch('user/getInfo'),
    ]);
    resolve();
  });
});

const updateMeta = (store, to) => {
  const {
    documentTitle, sideBar, centered, stretch,
  } = to.meta;
  store.commit('pageTitle', documentTitle);
  store.commit('sidebar', sideBar);
  store.commit('centered', centered);
  store.commit('stretch', stretch);
};

const initialPageByPerms = () => {
  const perms = $store.getters['user/allowedUserPages'];
  if (perms.includes('hotels')) return 'hotels';
  if (perms.includes('users')) return 'users';
  if (perms.includes('group')) return 'group';
  return 'noperms';
};

const initialPageForHotel = () => {
  const perms = $store.getters['user/allowedPages'];
  if (!perms.length) return 'noperms';
  if (perms.includes('calendar')) return 'dashboard';
  return perms[0];
};

const checkPageAccess = (route) => {
  const { meta, name } = route;
  const n = meta != null ? (meta.permission || name) : name;
  const h = $store.getters['user/allowedPages'];
  const u = $store.getters['user/allowedUserPages'];
  return h.includes(n) || u.includes(n);
};

const getInitialRoute = () => {
  const isAdmin = $store.getters['user/isAdmin'];
  if (isAdmin) return 'admin-groups';
  const hasHotels = $store.getters['user/hasHotels'];
  return hasHotels ? initialPageForHotel() : initialPageByPerms();
};

const updateTranslations = async (to) => {
  const commonTranslations = ['validation', 'links'];
  if (['login'].includes(to.meta.translationKey)) {
    commonTranslations.push(to.meta.translationKey);
    const data = await ApiService.post(`${translationToolUrl}/gateway/translations`, {
      project: translationToolProject,
      pages: commonTranslations,
    });
    Object.keys(data.data)
      .forEach((locale) => {
        i18n.setLocaleMessage(locale, data.data[locale]);
      });
  } else {
    const locales = require.context('../i18n/locales', true, /[A-Za-z0-9-_,\s]+\.json$/i);
    const messages = {};
    locales.keys().forEach((key) => {
      const matched = key.match(/([A-Za-z0-9-_]+)\./i);
      if (matched && matched.length > 1) {
        const locale = matched[1];
        messages[locale] = locales(key);
        i18n.setLocaleMessage(locale, messages[locale]);
      }
    });
  }
};

router.pushInitial = () => {
  const name = getInitialRoute();
  router.push({ name });
};

router.beforeEach(async (to, from, next) => {
  // eslint-disable-next-line no-underscore-dangle
  const _next = next;
  // eslint-disable-next-line no-param-reassign
  next = function newnext(loc) {
    return _next(loc);
  };
  initStore();

  await updateTranslations(to);

  if (!initialUserCheckDone) {
    await checkLoggedIn();
    initialUserCheckDone = true;
  }
  if ($store.getters['system/maintenanceIsOn']) {
    if (to.fullPath !== '/maintenance') {
      next({ name: 'maintenance' });
    }
  }

  // clear server-validation states in store modules
  $store.dispatch('clearErrors');

  // const {
  //   title, sideBar, centered, stretch,
  // } = to.meta;
  // $store.commit('pageTitle', title);
  // $store.commit('sidebar', sideBar);
  // $store.commit('centered', centered);
  // $store.commit('stretch', stretch);

  // By default all routes are protected for users only
  const isGuestOnly = to.matched.some((r) => r.meta.guestOnly);
  const isPublic = to.matched.some((r) => r.meta.public);
  const isForAdmin = to.matched.some((r) => r.meta.admin === true);
  const forEmptyHotels = to.matched.some((r) => r.meta.empty === true);
  const skipPerms = to.matched.some((r) => r.meta.perms === false);
  const disabledForAgent = to.matched.some((r) => r.meta.agent === false);
  const isLoggedIn = !!StorageService.getUser();
  const isForMaintenanceMode = to.matched.some((r) => r.meta.maintenance === true);

  const hasHotels = $store.getters['user/hasHotels'];
  const isAdmin = $store.getters['user/isAdmin'];
  const isAgentDomain = $store.getters['user/isAgentDomain'];
  const isAgentUser = $store.getters['user/isAgentUser'];
  const maintenanceIsOn = $store.getters['system/maintenanceIsOn'];

  // prevent access to maintenance page without maintenance enabled
  if (isForMaintenanceMode) {
    if (to.fullPath === '/maintenance' && !maintenanceIsOn) return next({ name: 'login' });
  }

  // prevent guests from visiting logged-in pages
  if (!isPublic && !isLoggedIn) {
    const redirect = to.fullPath;
    const toLogin = { name: 'login' };
    if (redirect !== '/') {
      toLogin.query = { redirect };
    }
    return next(toLogin);
  }

  if (to.name === 'home') {
    return next({ name: getInitialRoute() });
  }

  if (!isLoggedIn) {
    // prevent guests from visiting pages disabled for agent users
    if (isAgentDomain && disabledForAgent) {
      return next({ name: 'login' });
    }
    updateMeta($store, to);
    return next();
  }

  const initial = hasHotels ? initialPageForHotel() : initialPageByPerms();

  // prevent logged-in users from visiting guests-only pages
  // or pages disabled for agent users
  if (isLoggedIn && (isGuestOnly || (isAgentUser && disabledForAgent))) {
    return next({ name: initial });
  }

  // check conditions that prevents users from continue
  // until they fill some information or make some actions
  // ---
  // initial check has been done already, we can use $store here
  // const { $store } = router.app;

  // check for verified email
  if (!$store.getters['user/emailVerified']) {
    if (from.name === 'verification.notice') {
      return next(false);
    }
    if (to.name !== 'verification.notice') {
      return next({ name: 'verification.notice' });
    }
    updateMeta($store, to);
    return next();
  }
  if (to.name === 'verification.notice') {
    return next({ name: initial });
  }
  // ***

  // check for filled information
  if (!$store.getters['user/requiredFilled']) {
    if (from.name === 'details') {
      return next(false);
    }
    if (to.name !== 'details') {
      return next({ name: 'details' });
    }
    updateMeta($store, to);
    return next();
  }
  if (to.name === 'details') {
    return next({ name: initial });
  }
  // // ***

  // check for setup
  if (!$store.getters['user/setupComplete']) {
    if (from.name === 'setup') {
      return next(false);
    }
    if (to.name !== 'setup') {
      return next({ name: 'setup' });
    }
    updateMeta($store, to);
    return next();
  }
  if (to.name === 'setup') {
    return next({ name: initial });
  }
  // // ***

  // check for admin rights
  if (isForAdmin && !isAdmin) {
    return next({ name: initial });
  }

  // check for no hotels
  if (!hasHotels && !forEmptyHotels) {
    return next({ name: initial });
  }

  // check for permissions
  if (!isAdmin && !checkPageAccess(to) && !skipPerms && to.name !== 'noperms') {
    return next({ name: initial });
  }

  updateMeta($store, to);
  return next();
});
