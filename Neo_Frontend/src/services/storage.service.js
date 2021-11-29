export const USER_KEY = 'user';
export const LANG_KEY = 'lang';
export const HOTEL_KEY = 'hotel';

const listeners = {
  [USER_KEY]: [],
  [LANG_KEY]: [],
  // [HOTEL_KEY]: [],
};

function dispatcher(e) {
  const { key, newValue } = e;
  if (listeners[key] == null || !listeners[key].length) return;
  let val;
  try {
    val = JSON.parse(newValue);
  } catch (err) {
    val = newValue;
  }
  listeners[key].forEach((func) => func(val));
}
window.addEventListener('storage', dispatcher);

export const Storage = {
  addListener(key, func) {
    if (listeners[key] == null) return;
    listeners[key].push(func);
  },
  getUser() {
    try {
      return JSON.parse(localStorage.getItem(USER_KEY));
      // console.log(USER)
      // return JSON.parse(USER);
    } catch (e) {
      return null;
    }
  },
  getUserId(user = null) {
    if (user != null) return user;
    const u = this.getUser();
    return u != null ? u.id : null;
  },
  setUser(user) {
    localStorage.setItem(USER_KEY, JSON.stringify(user));
  },
  removeUser() {
    localStorage.removeItem(USER_KEY);
  },
  getLang() {
    const lang = localStorage.getItem(LANG_KEY);
    return lang != null ? lang : 'en';
  },
  setLang(lang) {
    localStorage.setItem(LANG_KEY, lang);
  },
  removeLang() {
    localStorage.removeItem(LANG_KEY);
  },
  getHotel(user = null) {
    let hotels = null;
    try {
      hotels = JSON.parse(localStorage.getItem(HOTEL_KEY));
    } catch (e) {
      return null;
    }
    if (hotels == null) return null;
    const id = this.getUserId(user);
    if (id == null) return null;
    const hotel = hotels[id];
    return hotel != null ? parseInt(hotel, 10) : null;
  },
  setHotel(hotel, user = null) {
    if (hotel == null) {
      // this.removeHotel();
      return;
    }
    const id = this.getUserId(user);
    if (id == null) return;
    let hotels;
    try {
      hotels = JSON.parse(localStorage.getItem(HOTEL_KEY));
      if (hotels == null || typeof hotels !== 'object') {
        hotels = {};
      }
    } catch (e) {
      hotels = {};
    }
    hotels[id] = hotel;
    localStorage.setItem(HOTEL_KEY, JSON.stringify(hotels));
  },
  removeHotel(user = null) {
    const id = this.getUserId(user);
    if (id == null) return;
    let hotels;
    try {
      hotels = JSON.parse(localStorage.getItem(HOTEL_KEY));
      if (hotels == null || typeof hotels !== 'object') {
        hotels = {};
      }
    } catch (e) {
      return;
    }
    if (hotels[id] != null) {
      delete hotels[id];
      localStorage.setItem(HOTEL_KEY, JSON.stringify(hotels));
    }
  },
  removeHotels() {
    localStorage.removeItem(HOTEL_KEY);
  },
};
export default Storage;
