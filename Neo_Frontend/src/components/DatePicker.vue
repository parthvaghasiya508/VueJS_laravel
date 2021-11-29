<template>
  <div class="form-control d-flex align-items-center drop-down-container date-picker"
       :id="$uid"
       :disabled="disabled"
       :tabindex="disabled ? -1 : 0"
       @focus="focused"
       @blur="blurred"
       @mousedown="lastEvent = 'm'"
       @keydown="lastEvent = 'k'"
       @click="clicked"
  >
    <icon class="icon mr-3" width="14" height="15" type="calendar"/>
    <span class="flex-grow-1">{{ title }}</span>
    <icon class="arrow" stroke-width="2" width="12" height="7" type="arrow-down"/>
    <div ref="menu" class="drop-down-menu position-absolute d-none" :class="menuClass">
      <div class="date-picker-my text-center">{{ monthYear }}</div>
      <nav class="d-flex align-items-center m-0">
        <b-btn tabindex="-1" class="btn-icon btn-icon-round"
               variant="outline-primary" @mousedown.stop.prevent="prevYear">
          <icon class="rel-left" stroke-width="1" width="8" height="12" type="arrow-left-filled"/>
        </b-btn>
        <b-btn tabindex="-1" class="btn-icon btn-icon-round"
               variant="outline-primary" @mousedown.stop.prevent="prevMonth">
          <icon class="rel-left" stroke-width="1" width="8" height="12" type="arrow-left"/>
        </b-btn>
        <b-btn variant="text" class="text-center flex-grow-1" @mousedown.stop.prevent="goToday">
          {{ $t('datepicker.today') }}
        </b-btn>
        <b-btn tabindex="-1" class="btn-icon btn-icon-round"
               variant="outline-primary" @mousedown.stop.prevent="nextMonth">
          <icon class="rel-right" stroke-width="1" width="8" height="12" type="arrow-right"/>
        </b-btn>
        <b-btn tabindex="-1" class="btn-icon btn-icon-round"
               variant="outline-primary" @mousedown.stop.prevent="nextYear">
          <icon class="rel-right" stroke-width="1" width="8" height="12" type="arrow-right-filled"/>
        </b-btn>
      </nav>
      <div class="wdays d-flex text-sm font-weight-bold">
        <div v-for="(wd, idx) in weekdays" :key="`wd-${idx}`"
             :class="{ 'text-primary': idx > 4 }">{{ wd }}
        </div>
      </div>
      <div class="days d-flex flex-wrap text-sm">
        <div v-for="(d, idx) in days" :key="idx" @mousedown.stop.prevent="select(d)"
             :class="{ 'today': d.today, 'current': d.current, 'disabled': d.disabled }">
          {{ d.day }}
        </div>
      </div>
    </div>
  </div>
</template>

<script>
  import moment from 'moment';

  export default {
    name: 'DatePicker',
    props: {
      placeholder: String,
      format: String,
      value: {
        type: [Date, moment, String],
      },
      minDate: {
        type: [Date, moment],
      },
      maxDate: {
        type: [Date, moment],
      },
      minToday: {
        type: Boolean,
        default: false,
      },
      position: {
        type: String,
        default: 'left',
        validate: (p) => ['left', 'right'].indexOf(p) !== -1,
      },
      grow: {
        type: String,
        default: 'down',
        validate: (g) => ['up', 'down', 'md-up'].indexOf(g) !== -1,
      },
      disabled: Boolean,
    },
    data: () => ({
      // shown: false,
      lastEvent: null,
      preventClick: false,
      menuClass: '',
      current: null,
      today: null,
    }),
    created() {
      this.menuClass = `from-${this.position} grow-${this.grow}`;
      this.resetCurrent();
    },
    computed: {
      title() {
        const obj = moment.isMoment(this.value) ? moment(this.value.toDate()) : this.value;
        return this.value == null || !this.value
          ? (this.placeholder || this.$t('datepicker.no-value'))
          : moment(obj).format(this.format || this.$t('datepicker.value-format'));
      },
      monthYear() {
        const my = moment(this.current.toDate()).format(this.$t('datepicker.title-format'));
        return my.capitalize();
      },
      weekdays() {
        // add i18n reactivity
        this.$te('_');
        const t = moment('1970-01-05'); // Monday
        const ret = [];
        for (let i = 0; i < 7; i += 1) {
          const d = t.format('dd');
          ret.push(d.capitalize());
          t.add(1, 'day');
        }
        return ret;
      },
      resultMinDate() {
        return this.minToday ? this.today : this.minDate;
      },
      days() {
        const today = moment();
        const todayDay = this.current.isSame(today, 'month') ? today.date() : false;
        const value = this.value != null ? moment(this.value) : null;
        const currentDay = value != null && value.isSame(this.current, 'month') ? value.date() : false;
        let disabledBefore = false;
        let disabledAfter = false;
        if (this.resultMinDate != null) {
          if (this.current.isBefore(this.resultMinDate, 'month')) {
            disabledBefore = 99;
          } else if (this.current.isSame(this.resultMinDate, 'month')) {
            disabledBefore = this.resultMinDate.date();
          }
        }
        if (this.maxDate != null) {
          if (this.current.isAfter(this.maxDate, 'month')) {
            disabledBefore = -1;
          } else if (this.current.isSame(this.maxDate, 'month')) {
            disabledAfter = this.maxDate.date();
          }
        }
        let start = this.current.day() - 1;
        if (start < 0) start = 6;
        const ret = [];
        for (let i = 0; i < start; i += 1) ret.push({});
        const date = moment(this.current);
        do {
          const d = { day: date.date() };
          if (d.day === todayDay) d.today = true;
          if (d.day === currentDay) d.current = true;
          if (disabledBefore && d.day < disabledBefore) d.disabled = true;
          if (disabledAfter && d.day > disabledAfter) d.disabled = true;
          ret.push(d);
          date.add(1, 'day');
        } while (this.current.isSame(date, 'month'));
        if (['up', 'md-up'].includes(this.grow)) {
          for (let i = 42; i >= ret.length; i -= 1) ret.push({});
        }
        return ret;
      },
    },
    methods: {
      show() {
        if (this.disabled) return;
        this.resetCurrent();
        this.$refs.menu.classList.remove('d-none');
        this.today = moment();
        // this.shown = true;
      },
      hide() {
        this.$refs.menu.classList.add('d-none');
        // this.shown = false;
      },
      toggle() {
        if (this.$refs.menu.classList.contains('d-none')) {
          this.show();
        } else {
          this.hide();
        }
        // this.$refs.menu.classList.toggle('d-none');
        // this.shown = !this.shown;
      },
      resetCurrent() {
        this.current = (this.value ? moment(this.value) : moment())
          .startOf('month')
          .startOf('day');
      },
      focused() {
        this.preventClick = this.lastEvent === 'm';
        this.show();
      },
      blurred() {
        this.hide();
      },
      clicked(e) {
        const { classList, parentNode: { classList: parentClassList } } = e.target;
        if (!classList.contains('date-picker') && !parentClassList.contains('date-picker')) return;
        if (!this.preventClick) {
          this.toggle();
        }
        this.preventClick = false;
      },
      prevMonth() {
        this.current = moment(this.current)
          .subtract(1, 'month');
      },
      nextMonth() {
        this.current = moment(this.current)
          .add(1, 'month');
      },
      prevYear() {
        this.current = moment(this.current)
          .subtract(1, 'year');
      },
      nextYear() {
        this.current = moment(this.current)
          .add(1, 'year');
      },
      goToday() {
        this.current = moment()
          .startOf('month')
          .startOf('day');
      },
      select(day) {
        if (day.day == null || day.disabled) return;
        this.$emit('input', moment(this.current).date(day.day));
        setTimeout(this.hide, 100);
      },
    },
  };
</script>
