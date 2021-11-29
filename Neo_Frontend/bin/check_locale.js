#!/usr/bin/env node

/* eslint-disable no-console */

const path = require('path');
const fs = require('fs');

const base = path.resolve(__dirname, '../src/i18n/locales');
const enPath = path.resolve(base, 'en.json');

if (!fs.existsSync(enPath)) {
  console.error('[en] locale doesn’t exist');
  process.exit(1);
}

const argv = process.argv.slice(2);
if (!argv || !argv.length) {
  console.log(`Usage: ${path.basename(__filename)} [-e] [-d] <locale>`);
  process.exit(1);
}

let errorsOnly = false;
let dumpMissing = false;

let locale = argv.shift()
  .toLowerCase();
if (locale === '-e') {
  errorsOnly = true;
  locale = argv.shift()
    .toLowerCase();
}
if (locale === '-d') {
  dumpMissing = true;
  locale = argv.shift()
    .toLowerCase();
}

const localePath = path.resolve(base, `${locale}.json`);
if (!fs.existsSync(localePath)) {
  console.error(`[${locale}] locale doesn’t exist`);
  process.exit(1);
}

console.log(`Check locale [${locale}]...`);

const en = JSON.parse(fs.readFileSync(enPath, 'utf8'));
const other = JSON.parse(fs.readFileSync(localePath, 'utf8'));

let errors = 0;
let warnings = 0;

function ignoreDup(pathArr, val) {
  return ['slug', 'name'].includes(pathArr[pathArr.length - 1])
    || [
      'OK', 'Status', 'IDs', 'Name', 'Standard', 'Position', 'URLs', 'Software',
    ].includes(val)
    || [
      'id',
      'datepicker.value-format',
      'datepicker.title-format',
      'placeholder.email',
      'restrictions.minlos',
      'restrictions.maxlos',
      'pages.reservations.formats.check-date',
      'pages.reservations.formats.check-time',
      'pages.calendar.scroller-date-format',
      'pages.policies.cancel.cancel-time',
      // 'pages.policies.cancel.cancel-fee.FullStay',
      'pages.policies.payment.payment-fee',
      'pages.policies.payment.types.Deposit',
      'pages.roomtypes.modal.title-edit',
      'pages.rateplans.modal.title-edit',
      'pages.rateplans.modal.no-payment-policy.desc',
      'pages.groups.modal.field.url.placeholder',
      'pages.users.modal.invite.field-email-placeholder',
      'pages.users.modal.newUser.field.name.first.placeholder',
      'pages.users.modal.newUser.field.name.last.placeholder',
      'pages.users.modal.newUser.field.login.placeholder',
      'pages.invoices.format',
      'pages.channels.connect.transmission-format',
      'pages.hotels.headers.create-date-format',
      'pages.booking.headers.date-format',
      'pages.hotels.modal.title-edit',
      'pages.mealplans.modal.title-edit',
      'pages.invoices.sum-format',
    ].includes(pathArr.join('.'));
}

const missingKeys = [];

function compareObjects(src, dst, keypath = []) {
  Object.keys(src)
    .forEach((key) => {
      const sval = src[key];
      const dval = dst[key];
      const stype = typeof sval;
      const dtype = typeof dval;
      // if(Array.isArray(sval)) stype = 'array';
      // if(Array.isArray(dval)) dtype = 'array';
      const p = [...keypath, key];
      const ps = p.join('.');
      if (dval == null) {
        if (!dumpMissing) {
          console.error(`${ps}> missing key`);
          errors += 1;
        } else {
          missingKeys.push(ps);
        }
      } else if (stype !== dtype) {
        console.error(`${ps}> type mismatch: ${stype} <-> ${dtype}`);
        errors += 1;
      } else if (stype === 'string') {
        // check for placeholders
        const svars = sval.match(/{[^}]+}/g) || [];
        const dvars = dval.match(/{[^}]+}/g) || [];
        if (svars.length !== dvars.length) {
          console.error(`${ps}> placeholders mismatch:\n  "${sval}"\n  "${dval}"`);
          errors += 1;
        } else if (svars.length > 0) {
          const missing = svars.reduce((acc, val) => (dvars.includes(val) ? acc : [...acc, val]), []);
          if (missing.length) {
            console.error(`${ps}> missing placeholder: ${missing.join(', ')}`);
            errors += 1;
          }
          const leftovers = dvars.reduce((acc, val) => (svars.includes(val) ? acc : [...acc, val]), []);
          if (leftovers.length) {
            console.error(`${ps}> unknown placeholder: ${leftovers.join(', ')}`);
            errors += 1;
          }
        }

        // check for variations
        const sdig = sval.match(/ \| /g) || [];
        const ddig = dval.match(/ \| /g) || [];
        if (sdig.length !== ddig.length) {
          console.error(`${ps}> variations mismatch:\n  "${sval}"\n  "${dval}"`);
          errors += 1;
        }

        // check for untranslated string
        if (sval === dval && sval.indexOf('@:') !== 0 && !ignoreDup(p, sval)) {
          if (!errorsOnly) {
            console.warn(`${ps}> identical strings:\n  "${sval}"\n  "${dval}"`);
          }
          warnings += 1;
        }
      } else {
        compareObjects(sval, dval, p);
      }
    });
}

const pick = (obj, p) => {
  const pa = p.split('.');
  if (pa.length === 1) return obj[p];
  const ps = pa.shift();
  return pick(obj[ps], pa.join('.'));
};

function traverseKeys(obj, acc, keypath = []) {
  if (typeof obj === 'string') {
    acc.push([
      keypath.join('.'),
      obj
        .replace('\\', '\\\\')
        .replace('"', '\\"')
        .replace('\n', '\\n'),
    ]);
    return;
  }
  Object.keys(obj).forEach((key) => {
    traverseKeys(obj[key], acc, [...keypath, key]);
  });
}

console.log('------------------');
compareObjects(en, other);
if (errors > 0 || warnings > 0) {
  console.log('------------------');
  console.log(`Errors: ${errors} | Warnings: ${warnings}`);
  process.exit(1);
}

if (dumpMissing && missingKeys.length) {
  const csv = [];
  missingKeys.forEach((k) => {
    traverseKeys(pick(en, k), csv, [k]);
  });
  const dump = [
    'Key,EN,DE',
    ...csv.map(([k, v]) => `"${k}","${v}",""`),
  ].join('\n');
  console.log(dump);
} else {
  console.log('Everything OK');
}
process.exit(0);
