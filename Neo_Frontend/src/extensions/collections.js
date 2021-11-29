/* eslint-disable no-extend-native */

Array.prototype.pluck = function pluckKey(key) {
  return this.map(({ [key]: k }) => k);
};
Set.prototype.addAll = function addAllItems(items) {
  items.forEach((v) => this.add(v));
};
Array.prototype.toggle = function toggleArrayValue(value) {
  const idx = this.findIndex(value);
  if (idx === -1) {
    this.push(value);
  } else {
    this.splice(idx, 1);
  }
};
Set.prototype.toggle = function toggleSetValue(value) {
  if (this.has(value)) {
    this.delete(value);
  } else {
    this.add(value);
  }
};
