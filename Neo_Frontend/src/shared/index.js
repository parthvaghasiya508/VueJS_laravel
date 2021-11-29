export const host = window.location.host ?? window.location.hostname ?? process.env.VUE_APP_HOST;

export const appEndpoint = `${process.env.VUE_APP_INSECURE ? 'http' : 'https'}://api.${host}`;
export const apiEndpoint = `${appEndpoint}/api`;
export const cookieEndpoint = `${appEndpoint}/sanctum/csrf-cookie`;
// FIXME: needed?
export const externalEngineHost = process.env.VUE_APP_EXTERNAL_ENGINE_HOST;
export const externalEngineTpl = process.env.VUE_APP_EXTERNAL_ENGINE_TPL;
export const adminHost = process.env.VUE_APP_HOST;
export const adminHostTpl = process.env.VUE_APP_HOST_TPL;

export const translationToolUrl = 'https://api.translation.roomdb.io/api';
export const translationToolProject = 'neoFront';

export const quMainFields = [
  'avail',
  'price',
  'osale',
];

export const quMoreFields = [
  'minlos',
  'maxlos',
  'carr',
  'cdep',
  'grnt',
];

export const distanceUnits = ['km', 'm', 'ft', 'mi'];

export const weekdays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

export const locales = [
  { code: 'en', title: 'English' },
  { code: 'de', title: 'Deutsch' },
  { code: 'tr', title: 'Türkçe' },
  // { code: 'ru', title: 'Русский' },
];
export const langCodes = [
  'en', 'de', 'tr', 'fr', 'ru', 'zh', 'pt',
  'it', 'es', 'pl', 'nl', 'ro',
];

export const timeUnits = ['hour', 'day', 'week', 'month', 'year'];
export const dropTimes = ['BeforeArrival', 'AfterBooking', 'AfterConfirmation'];
export const basisTypes = ['FullStay', 'Nights'];
export const channelUpdateTypes = [
  { id: 0, text: 'channel-update-types.0' },
  { id: 1, text: 'channel-update-types.1' },
  { id: 2, text: 'channel-update-types.2' },
  { id: 3, text: 'channel-update-types.3' },
];
export const numberFormats = [
  { code: 'en-US', currency: 'USD' },
  { code: 'de-DE', currency: 'EUR' },
  { code: 'en-CA', currency: 'CAD' },
  { code: 'fr-CA', currency: 'CAD' },
  { code: 'en-AU', currency: 'AUD' },
  { code: 'en-IE', currency: 'EUR' },
  { code: 'nl-NL', currency: 'EUR' },
  { code: 'en-GB', currency: 'GBP' },
  { code: 'ja-JP', currency: 'JPY' },
  { code: 'en-NZ', currency: 'NZD' },
  { code: 'zh-CN', currency: 'CNY' },
  { code: 'zh-HK', currency: 'CNH' },
  { code: 'da-DK', currency: 'DKK' },
];
export const fakeNumber = 123456789;

export const bookable = {
  anytime: 0,
  periods: 1,
  fromto: 2,
  until: 3,
  within: 4,
};


export const billingPages = [
  { name: 'invoices' },
  { name: 'payments' },
];

export const defaultTextColors = {
  black: {
    rgba: {
      r: '0',
      g: '0',
      b: '0',
      a: '1',
    },
  },
  white: {
    rgba: {
      r: '255',
      g: '255',
      b: '255',
      a: '1',
    },
  },
};

export const defaultBackgroundColor = {
  rgba: {
    r: '74',
    g: '144',
    b: '226',
    a: '1',
  },
};

export const domainValid = (value) => /^([a-z][a-z0-9-]+)(\.[a-z][a-z0-9-]+)+$/.test(value);

export const widgetId = {
  BookingDay: 0,
  CancellationRate: 1,
  RoomnightsBooking: 2,
  ProducingChannels: 3,
  AvgBookingValue: 4,
  OnlineOccupancyRate: 5,
  AvgDailyRoomRate: 6,
  OnlineRevPAR: 7,
  BookingValue: 8,
  SoldRoomnights: 9,
  IBESearches: 10,
  UniqueVisitors: 11,
  IBEBookings: 12,
  SearchesVisitor: 13,
  Conversion: 14,
  IBEPerformance: 15,
  DirectIndirect: 16,
  BookingTable: 17,
  BookingVolumeTable: 18,
  BookingVolume: 19,
  AccumulatedBookingVolume: 20,
};

export const widgetType = {
  scoreCard: 1,
  gaugeChart: 2,
  pieChart: 3,
  waterfallChart: 4,
  table: 5,
  lineChart: 6,
};

export const positionSplitChar = '-';
export const sizeSplitChar = 'x';
export const arcColors = ['rgb(255,84,84)', 'rgb(239,214,19)', 'rgb(61,204,91)'];

export const timePeriodDashboardKeys = [
  'D-1',
  'D-1_D-7',
  'D-1_D-30',
  'D-1_D-90',
  'D-1_D-365',
  'W-1',
  'YTD-1',
  'YTD-1_LY',
  'M-1',
  'Y-1',
];

export const paymentMethods = {
  sepa: 'sepa_debit',
  card: 'card',
  bank: 'bancontact',
};

export const bankDetails = {
  client: 'CultSwitch GmbH',
  bank: 'Deutsche Bank',
  iban: 'DE68 1007 0000 0048 0921 00',
  bic: 'DEUTDEBBXXX',
};

export const systemList = {
  apaleo: {
    id: Number(process.env.VUE_APP_APALEO_PMS_ID),
    name: 'Apaleo',
  },
  hostaway: {
    id: Number(process.env.VUE_APP_HOSTAWAY_PMS_ID),
    name: 'Hostaway',
  },
};

export const bookingEngineId = process.env.VUE_APP_DEFAULT_PULL_CHANNEL;
export const minPaginationPerPage = 10;

export const defaultSettingColors = {
  template_background_type: 'flat',
  header_background_type: 'flat',
  other_buttons_background_type: 'flat',
  product_background_type: 'flat',
  template_background_color: '#FFFFFF',
  template_background_color_top: '#FFFFFF',
  template_background_color_bottom: '#FFFFFF',
  header_background_color: '#000000',
  header_background_color_top: '#000000',
  header_background_color_bottom: '#000000',
  product_background_color: '#FFFFFF',
  product_background_color_top: '#FFFFFF',
  product_background_color_bottom: '#FFFFFF',
  input_background_color: '#FFFFFF',
  search_button_color: '#FFFFFF',
  search_button_background_color: '#F7981C',
  search_button_background_hover_color: '#E59730',
  other_buttons_color: '#FFFFFF',
  other_buttons_background_color: '#f7981c',
  other_buttons_background_hover_color: '#39b54a',
  other_buttons_background_color_top: '#f7981c',
  other_buttons_background_color_bottom: '#f7981c',
  other_buttons_background_hover_color_top: '#39b54a',
  other_buttons_background_hover_color_bottom: '#39b54a',
  language_block_color: '#666',
  date_background_color: '#FFFFFF',
  date_color: '#545d6c',
  product_name_color: '#4A90E2',
  meals_included_colour: '#26D237',
  meals_excluded_colour: '#F52525',
  product_note_color: '#4A90E2',
  product_amount_color: '#4A90E2',
  product_name_font_style: 'normal',
  product_note_font_style: 'normal',
  product_amount_font_style: 'normal',
  check_in_out_color: '#FFFFFF',
  title_bar_color: '#91929d',
  title_bar_background_color: '#000000',
  input_color: '#666666',
  input_error_border_color: '#F52525',
  input_success_border_color: '#26D237',
};
export const defaultProjectId = 1;

// specifies order for config settings
export const groupConfig = [
  'hide-login-footer-links',
];

export const currencySymbols = {
  EUR: '€',
  USD: '$',
  JPY: '¥',
  BGN: 'Лв',
  CZK: 'Kč',
  DKK: 'kr',
  GBP: '£',
  HUF: 'ft',
  LTL: 'LTL',
  LVL: 'LVL',
  PLN: 'zł',
  RON: 'lei',
  SEK: 'kr',
  CHF: 'CHF',
  NOK: 'kr',
  HRK: 'kn',
  RUB: 'RUB',
  TRY: 'TRY',
  AUD: '$',
  BRL: 'R$',
  CAD: '$',
  CNY: '¥',
  HKD: 'HK$',
  IDR: 'Rp',
  ILS: 'ILS',
  INR: 'INR',
  KRW: 'KRW',
  MXN: '$',
  MYR: 'RM',
  NZD: '$',
  PHP: 'PHP',
  SGD: '$',
  THB: 'THB',
  ZAR: 'R',
  EEK: 'EEK',
  SKK: 'SKK',
  EGP: 'ج.م',
  RSD: 'din',
  TND: 'د.ت',
  AED: 'د.إ',
  CLP: '$',
  ARS: '$',
  LBP: 'ل.ل.',
  TZS: 'TSh',
  MAD: 'DH',
  MUR: 'MUR',
  NAD: 'N$',
  PEN: 'S',
  NGN: 'NGN',
  UAH: 'UAH',
  COP: '$',
  VND: 'VND',
  SAR: 'SAR',
  BDT: '৳',
};

export default {
  host,
  adminHost,
  adminHostTpl,
  appEndpoint,
  apiEndpoint,
  cookieEndpoint,
  externalEngineHost,
  externalEngineTpl,

  quMainFields,
  quMoreFields,

  locales,
  weekdays,
  timeUnits,
  dropTimes,
  basisTypes,
  numberFormats,
  fakeNumber,
  bookable,


  defaultTextColors,
  defaultBackgroundColor,

  domainValid,

  paymentMethods,
  bankDetails,

  widgetId,
  widgetType,

  positionSplitChar,
  sizeSplitChar,
  timePeriodDashboardKeys,

  systemList,
  bookingEngineId,
  minPaginationPerPage,
  defaultSettingColors,
  defaultProjectId,

  groupConfig,
  translationToolUrl,
  translationToolProject,
  currencySymbols,
};
