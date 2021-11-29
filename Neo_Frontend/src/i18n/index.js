import Vue from 'vue';
import VueI18n from 'vue-i18n';
import moment from 'moment';

Vue.use(VueI18n);

// eslint-disable-next-line import/no-mutable-exports
let i18n = null;

let currentLocale = null;
let localeWeekdays = (new Array(7)).fill('');
let localeWeekdays2 = (new Array(7)).fill('');

function updateWeekdays() {
  currentLocale = i18n.locale;
  const monday = moment('1970-01-05');
  localeWeekdays = localeWeekdays.map(() => {
    const d = monday.format('ddd');
    monday.add(1, 'day');
    return d.capitalize();
  });
  localeWeekdays2 = localeWeekdays2.map(() => {
    const d = monday.format('dd');
    monday.add(1, 'day');
    return d.capitalize();
  });
}

const i18nExtends = {
  format: {
    datetime: (ctx) => {
      let obj = ctx.list(0);
      if (obj == null || !obj) return '';
      const fmt = ctx.list(1);
      if (obj instanceof Date || typeof obj === 'string') {
        obj = moment(obj).locale(moment.locale());
      }
      const format = i18n.te(fmt) ? i18n.t(fmt) : fmt;
      return obj.format(format);
    },
  },
  weekdays() {
    if (currentLocale !== i18n.locale) {
      updateWeekdays();
    }
    return localeWeekdays;
  },
  weekdays2() {
    if (currentLocale !== i18n.locale) {
      updateWeekdays();
    }
    return localeWeekdays2;
  },
};

const extendMessages = (msgs) => ({
  ...msgs,
  ...i18nExtends,
});

const loadLocales = () => {
  const context = require.context('./locales', true);
  const messages = context.keys()
    .map((key) => ({ key, locale: key.match(/[-a-z0-9_]+/i)[0] }))
    .reduce((msgs, { key, locale }) => ({
      ...msgs,
      [locale]: extendMessages(context(key)),
    }), {});
  return { context, messages };
};

const { context, messages } = loadLocales();

i18n = new VueI18n({
  locale: 'en',
  fallbackLocale: 'en',
  // fallbackLocale: '__',
  silentFallbackWarn: true,
  messages,
});

if (module.hot) {
  module.hot.accept(context.id, () => {
    const { messages: newMessages } = loadLocales();
    Object.keys(newMessages)
      .filter((locale) => messages[locale] !== extendMessages(newMessages[locale]))
      .forEach((locale) => {
        const msgs = extendMessages(newMessages[locale]);
        messages[locale] = msgs;
        i18n.setLocaleMessage(locale, msgs);
        // console.debug(`Changed locale: ${locale}`);
      });
  });
}

export default i18n;
