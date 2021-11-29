/* eslint-disable no-extend-native */

String.prototype.capitalize = function capitalizeString() {
  return `${this.charAt(0).toUpperCase()}${this.slice(1).toLowerCase()}`;
};
String.prototype.capitalizeFirst = function capitalizeString() {
  return `${this.charAt(0).toUpperCase()}${this.slice(1)}`;
};
String.prototype.capitalizeAll = function capitalizeWords() {
  return this.split(' ').map((word) => word.capitalize()).join(' ');
};
String.prototype.slugify = function slugify() {
  return this.toLowerCase()
    .replaceAll(/@/g, '-at-')
    .replaceAll(/&/g, '-and-')
    .replaceAll(/_+/g, '-')
    .replaceAll(/[^-a-z0-9 ]+/g, '')
    .replaceAll(/[- ]+/g, '-')
    .replaceAll(/-?(.*)-?/g, '$1');
};
