/* eslint-disable no-nested-ternary */
/* eslint-disable no-nested-ternary */
<template>
  <div class="page-calendar" id="page-calendar">
    <div class="panel">
      <div class="panel-title position-relative w-100 title">
        <p>{{ $t('pages.calendar.title') }}</p>
        <button class="btn-icon position-absolute text-primary d-xl-none"
                :disabled="!roomsLoaded" @click="resetQuickUpdate" v-b-modal.quickUpdateModal>
          <icon width="27" height="21" type="quick-update"/>
        </button>
      </div>
    </div>

    <b-modal
      no-close-on-backdrop
      id="quickUpdateModal"
      ref="quickUpdateModal"
      no-fade
      centered
      static
      size="lg"
      modal-class="form-modal"
      :ok-title="$t('buttons.update')"
      ok-variant="primary"
      :cancel-title="$t('buttons.cancel')"
      cancel-variant="outline-primary"
      :ok-disabled="dataUpdating || !$refs.quickUpdateForm || !formQUValid || !$refs.quickUpdateForm.flags.valid"
      :cancel-disabled="dataUpdating"
      :no-close-on-esc="dataUpdating"
      :hide-header-close="dataUpdating"
      @ok.prevent="processQuickUpdate"
    >
      <template #modal-header-close>
        <icon width="20" height="20" class="d-none d-md-block" type="times"/>
        <icon width="10" height="18" class="d-md-none" type="arrow-left"/>
      </template>
      <template #modal-title>
        {{ $t('pages.calendar.update-modal.title') }}
      </template>
      <ValidationObserver ref="quickUpdateForm" slim>
        <div class="edge" :class="{ active: quickUpdate.weekdays.length }">
          <h3 :content="$t('pages.calendar.update-modal.select-range-and-days')"></h3>
          <h5>{{ $t('pages.calendar.update-modal.date-range') }}</h5>
          <div class="row">
            <div class="col-12 cell-qu-from">
              <label class="text-xs" for="date-qu-from">{{ $t('date.from') }}</label>
              <date-picker id="date-qu-from" v-model="quickUpdate.from" :min-date="today"
                           :disabled="dataPending || dataUpdating"
                           :format="dateFormat" @input="fromQUChanged"/>
            </div>
            <div class="col-12 cell-qu-until">
              <label class="text-xs" for="date-qu-until">{{ $t('date.until') }}</label>
              <date-picker id="date-qu-until" v-model="quickUpdate.until" :min-date="today"
                           ref="dateQUUntil" position="left-md-right"
                           :disabled="dataPending || dataUpdating"
                           :format="dateFormat" @input="untilQUChanged"/>
            </div>
          </div>
          <div class="row row-weekdays">
            <div class="col-sm-12 col-md-auto">
              <h5>{{ $t('pages.calendar.update-modal.days') }}</h5>
            </div>
            <div class="col-auto">
              <div class="d-flex cell-weekdays">
                <div class="wd-item wd-all">
                  <check-box v-model="allDays"
                             :disabled="dataUpdating">{{ $t('pages.calendar.update-modal.days-all') }}</check-box>
                </div>
                <div class="wd-item" v-for="wd in weekdays" :key="`wd-${wd}`">
                  <check-box v-model="quickUpdate.weekdays"
                             :disabled="dataUpdating"
                             :val="wd">{{wd}}</check-box>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="edge" :class="{ active: quickUpdate.selectedRooms.length }">
          <h3 :content="$t('pages.calendar.update-modal.select-rooms')"></h3>
          <div class="row">
            <div class="col cell-qu-rooms">
              <drop-down
                id="dd-qu-rooms"
                multiple
                select-all
                title-all="pages.calendar.rooms-all"
                title="pages.calendar.rooms"
                v-model="quickUpdate.selectedRooms"
                :items="rooms"
                :disabled="!roomsLoaded || dataUpdating"
              />
            </div>
          </div>
        </div>
        <div class="edge" :class="{ active: quHasMainFields }">
          <h3 :content="$t('pages.calendar.update-modal.what-to-edit')"></h3>
          <div class="row row-edit">
            <div class="col cell-edit-label">
              <check-box v-model="quickUpdate.do_avail"
                         :disabled="dataUpdating">{{ $t('pages.calendar.update-modal.avail') }}</check-box>
            </div>
            <div class="col cell-edit-field">
              <ValidatedField
                type="number" name="quavail" min="1" max="999" class="mb-0" no-icon no-tooltip
                v-model="quickUpdate.avail" :disabled="!quickUpdate.do_avail || dataUpdating"
                :rules="rulesFor('avail')"
              />
            </div>
            <div class="col cell-edit-tip">
              {{ $t('pages.calendar.update-modal.avail-tip') }}
            </div>
          </div>
          <div class="row row-edit">
            <div class="col cell-edit-label">
              <check-box v-model="quickUpdate.do_price"
                         :disabled="dataUpdating">{{ $t('pages.calendar.update-modal.price') }}</check-box>
            </div>
            <div class="col cell-edit-field">
              <ValidatedField
                type="number" name="quprice" min="1" max="999" class="mb-0" no-icon no-tooltip
                v-model="quickUpdate.price" :disabled="!quickUpdate.do_price || dataUpdating"
                :rules="rulesFor('price')"
              />
            </div>
            <div class="col cell-edit-tip">
              {{ $t('pages.calendar.update-modal.price-tip', { currency: currency }) }}
            </div>
          </div>
          <div class="row row-edit">
            <div class="col cell-edit-label">
              <check-box v-model="quickUpdate.do_osale"
                         :disabled="dataUpdating">{{ $t('pages.calendar.update-modal.status') }}</check-box>
            </div>
            <div class="col-auto cell-edit-field">
              <switcher v-model="quickUpdate.osale" :disabled="!quickUpdate.do_osale || dataUpdating"/>
            </div>
            <div class="col cell-edit-tip">
              {{ $t(`pages.calendar.update-modal.status-${quickUpdate.osale ? 'open' : 'closed'}`) }}
            </div>
          </div>
        </div>
        <div class="edge" :class="{ active: quHasMoreFields }">
          <h6 :class="{'opened': quMoreOpened}" @click="quMoreOpened=!quMoreOpened">
            {{ $t('pages.calendar.update-modal.change-more') }}
            <icon width="12" height="7" stroke-width="2" type="arrow-down"/>
          </h6>
          <div>
            <div class="row row-edit">
              <div class="col cell-edit-label">
                <check-box v-model="quickUpdate.do_minlos"
                           :disabled="dataUpdating">{{ $t('restrictions.minlos') }}</check-box>
              </div>
              <div class="col cell-edit-field">
                <ValidatedField
                  type="number" name="quminlos" min="1" max="999" class="mb-0" no-icon no-tooltip
                  v-model="quickUpdate.minlos" :disabled="!quickUpdate.do_minlos || dataUpdating"
                  :rules="rulesFor('minlos')"
                />
              </div>
              <div class="col cell-edit-tip">
                {{ $t('pages.calendar.update-modal.nights') }}
              </div>
            </div>
            <div class="row row-edit">
              <div class="col cell-edit-label">
                <check-box v-model="quickUpdate.do_maxlos"
                           :disabled="dataUpdating">{{ $t('restrictions.maxlos') }}</check-box>
              </div>
              <div class="col cell-edit-field">
                <ValidatedField
                  type="number" name="qumaxlos" min="1" max="999" class="mb-0" no-icon no-tooltip
                  v-model="quickUpdate.maxlos" :disabled="!quickUpdate.do_maxlos || dataUpdating"
                  :rules="rulesFor('maxlos')"
                />
              </div>
              <div class="col cell-edit-tip">
                {{ $t('pages.calendar.update-modal.nights') }}
              </div>
            </div>
            <div class="row row-edit">
              <div class="col cell-edit-label">
                <check-box v-model="quickUpdate.do_carr"
                           :disabled="dataUpdating">{{ $t('restrictions.carr') }}</check-box>
              </div>
              <div class="col cell-edit-fields">
                <radio v-model="quickUpdate.carr" :val="true" name="qucarr"
                       :disabled="!quickUpdate.do_carr || dataUpdating">{{ $t('yes') }}
                </radio>
                <radio v-model="quickUpdate.carr" :val="false" name="qucarr"
                       :disabled="!quickUpdate.do_carr || dataUpdating">{{ $t('no') }}
                </radio>
              </div>
            </div>
            <div class="row row-edit">
              <div class="col cell-edit-label">
                <check-box v-model="quickUpdate.do_cdep"
                           :disabled="dataUpdating">{{ $t('restrictions.cdep') }}</check-box>
              </div>
              <div class="col cell-edit-fields">
                <radio v-model="quickUpdate.cdep" :val="true" name="qucdep"
                       :disabled="!quickUpdate.do_cdep || dataUpdating">{{ $t('yes') }}
                </radio>
                <radio v-model="quickUpdate.cdep" :val="false" name="qucdep"
                       :disabled="!quickUpdate.do_cdep || dataUpdating">{{ $t('no') }}
                </radio>
              </div>
            </div>
            <div class="row row-edit row-qu-grnt">
              <div class="col cell-edit-label">
                <check-box v-model="quickUpdate.do_grnt"
                           :disabled="dataUpdating">{{ $t('bgarant') }}</check-box>
              </div>
              <div class="col cell-edit-fields">
                <radio v-model="quickUpdate.grnt" val="1" name="qubg"
                       :disabled="!quickUpdate.do_grnt || dataUpdating">{{ $t('bgarants.1.title') }}
                </radio>
                <radio v-model="quickUpdate.grnt" val="3" name="qubg"
                       :disabled="!quickUpdate.do_grnt || dataUpdating">{{ $t('bgarants.3.title') }}
                </radio>
              </div>
            </div>
          </div>
        </div>
      </ValidationObserver>
    </b-modal>

    <div class="sticky-top">
      <div class="row pt-3">
        <div class="col-12 cell-top-row">
          <div class="row">
            <div class="col-12 cell-rooms">
              <drop-down
                id="dd-rooms"
                multiple
                select-all
                title-all="pages.calendar.rooms-all"
                title="pages.calendar.rooms"
                v-model="selectedRooms"
                :items="rooms"
                :disabled="!roomsLoaded"
              />
            </div>
            <div class="col-12 cell-restrictions">
              <drop-down
                id="dd-restrictions"
                multiple
                select-all
                title-all="pages.calendar.restrictions-all"
                title="pages.calendar.restrictions"
                v-model="selectedRestrictions"
                :items="restrictionOptions"
                position="left"
              />
            </div>
          </div>
          <div class="row">
            <div class="col-6 cell-from">
              <label class="text-xs" for="date-from">{{ $t('date.from') }}</label>
              <date-picker id="date-from" v-model="minDate" :min-date="today"
                           :disabled="!roomsLoaded || dataPending"
                           :format="dateFormat" @input="fromChanged"/>
            </div>
            <div class="col-6 cell-until">
              <label class="text-xs" for="date-until">{{ $t('date.until') }}</label>
              <date-picker id="date-until" v-model="maxDate" :min-date="today" ref="dateUntil" position="left-md-right"
                           :disabled="!roomsLoaded || dataPending"
                           :format="dateFormat" @input="untilChanged"/>
            </div>
            <div class="col-12 align-self-end cell-button">
              <b-button block variant="secondary"
                        :disabled="!roomsLoaded || dataPending" @click="setPeriod">
                {{ $t('pages.calendar.button-search') }}
              </b-button>
            </div>
          </div>
        </div>
        <div class="col d-none d-xl-flex align-self-end justify-content-end">
          <b-btn variant="outline-primary" class="d-flex align-items-center"
                 :disabled="!roomsLoaded" @click="resetQuickUpdate" v-b-modal.quickUpdateModal>
            <icon width="27" height="21" class="mr-1" type="quick-update"/>
            {{ $t('pages.calendar.button-quick-update') }}
          </b-btn>
        </div>
      </div>
      <div class="panel position-relative panel-content dates-panel" v-if="periodSet">
        <div class="dates-header d-flex">
          <div class="dates-scroller">
            <span class="date-scroll" :class="{ disabled: !scrollLeftEnabled }" @click="scrollLeft('double')">
              <icon class="arrow" stroke-width="1" width="13" height="22" type="arrow-left"/>
              <icon class="arrow" stroke-width="1" width="13" height="22" type="arrow-left"/>
            </span>
            <span class="date-scroll" :class="{ disabled: !scrollLeftEnabled }" @click="scrollLeft('single')">
              <icon class="arrow" stroke-width="1" width="13" height="22" type="arrow-left"/>
            </span>
            <span class="date-scroll" :class="{ disabled: !scrollRightEnabled }" @click="scrollRight('single')">
              <icon class="arrow" stroke-width="1" width="13" height="22" type="arrow-right"/>
            </span>
            <span class="date-scroll" :class="{ disabled: !scrollRightEnabled }" @click="scrollRight('double')">
              <icon class="arrow" stroke-width="1" width="13" height="22" type="arrow-right"/>
              <icon class="arrow" stroke-width="1" width="13" height="22" type="arrow-right"/>
            </span>
          </div>
          <div class="dates-container">
            <overlay-scrollbars
              ref="datesHeader"
              :options="datesScrollOptions"
            >
              <div class="dates d-flex">
                <div class="month" v-for="month in dates" :key="month.month">
                  <div class="days d-flex">
                    <div class="day" v-for="(day, index) in month.days"
                         :key="index+day.d" :class="{'we': day.w>4, 'wee': day.w>5}">
                      <span class="t">{{ day.t }}</span>
                      <span class="wd">{{ day.d }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </overlay-scrollbars>
          </div>
        </div>
      </div>
    </div>

    <div class="day-panels" v-if="periodSet && dataLoaded">
      <div class="panel position-relative panel-content"
           v-for="{id, text} in rooms" :key="id"
           :class="{ 'd-none': !isRoomShown(id), 'first-shown': firstShownRoom(id) }">
        <div class="room-header">
          <span class="name">{{ text }}</span>
          <span class="id ml-2">({{ $t('pages.calendar.room-id', { id }) }})</span>
        </div>
        <div class="room-data d-flex">
          <div class="headers">
            <div class="header">{{ $t('pages.calendar.room-status') }}</div>
            <div class="header">{{ $t('pages.calendar.room-avail') }}</div>
            <div class="header"
                 :class="{ 'last-shown': !selectedRestrictions.length }">{{ $t('pages.calendar.room-price') }}
              <span class="asset" v-html="currencySymbol()"></span></div>
            <div class="header restriction"
                 :class="{ 'd-none': !isRestrictionShown(rts.id), 'last-shown': lastShownRestriction(rts.id) }"
                 v-for="rts in restrictionOptions"
                 :key="rts.id">
              <span>{{ rts.text }}</span>
              <icon width="16" height="16" v-b-tooltip:page-calendar.right="rts.tip" type="info"/>
            </div>
          </div>
          <div class="rows" ref="roomRows">
            <div class="data room-status">
              <div class="day" v-for="day in data[id]"
                   :key="day.i" :class="day.avail>0 && !day.csale ? 'bookable' : 'blocked'"></div>
            </div>
            <div class="data room-editable" data-field="avail">
              <div class="day" v-for="day in data[id]" @click.self="focusField($event)"
                   :key="day.i" :class="day.avail>0 && !day.csale ? 'bookable' : 'blocked'">
                <spinner v-if="isDayUpdating(id, day.i, 'avail')"/>
                <div v-else-if="isDayUpdated(id, day.i, 'avail')" class="day-updated">
                  <icon width="18" height="18" type="ok-tick"/>
                </div>
                <b-input :value="day.avail" v-else
                         @focus.prevent="selectField($event, day)"
                         @mouseup.stop.prevent
                         @blur="deselectField"
                         @keyup.esc="resetField"
                         @keyup.enter="updateField($event, id)"
                />
              </div>
            </div>
            <div class="data room-editable" data-field="price" :class="{ 'last-shown': !selectedRestrictions.length }">
              <div class="day" v-for="day in data[id]" @click.self="focusField($event)"
                   :key="day.i" :class="day.avail>0 && !day.csale ? 'bookable' : 'blocked'">
                <spinner v-if="isDayUpdating(id, day.i, 'price')"/>
                <div v-else-if="isDayUpdated(id, day.i, 'price')" class="day-updated">
                  <icon width="18" height="18" type="ok-tick"/>
                </div>
                <b-input :value="day.price" v-else
                         :disabled="day.avail==0"
                         @focus.prevent="selectField($event, day)"
                         @mouseup.stop.prevent
                         @blur="deselectField"
                         @keyup.esc="resetField"
                         @keyup.enter="updateField($event, id)"
                />
              </div>
            </div>
            <div v-for="rts in restrictionOptions"
                 :key="rts.id"
                 class="data room-editable"
                 :class="{ 'd-none': !isRestrictionShown(rts.id), 'last-shown': lastShownRestriction(rts.id) }"
                 :data-field="rts.fld">
              <div class="day" v-for="day in data[id]" @click.self="focusField($event)"
                   :key="day.i" :class="day.avail>0 && !day.csale ? 'bookable' : 'blocked'">
                <spinner v-if="isDayUpdating(id, day.i, rts.fld)"/>
                <div v-else-if="isDayUpdated(id, day.i, rts.fld)" class="day-updated">
                  <icon width="18" height="18" type="ok-tick"/>
                </div>
                <template v-else>
                  <b-input v-if="!rts.cb"
                           :value="day[rts.fld]"
                           :disabled="day.avail==0"
                           @focus.prevent="selectField($event, day)"
                           @mouseup.stop.prevent
                           @blur="deselectField"
                           @keyup.esc="resetField"
                           @keyup.enter="updateField($event, id)"
                  />
                  <check-box v-else
                             empty
                             :value="day[rts.fld]"
                             :disabled="day.avail==0"
                             @input="updateField($event, id, day, rts.fld)"
                  />
                </template>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <b-alert v-if="pmsError" class="mt-3" variant="danger" show>
      <h4 class="alert-heading">{{ $t('error') }}</h4>
      <p class="mb-0">{{ pmsError.response ? pmsError.response.data.message : pmsError }}</p>
    </b-alert>
  </div>
</template>

<script>
  import moment from 'moment';
  import { mapState, mapGetters, mapActions } from 'vuex';
  import {
    quMainFields, quMoreFields, weekdays, currencySymbols,
  } from '@/shared';
  import { formatNumber } from '@/helpers';

  export default {
    name: 'Calendar',
    data() {
      return {
        selectedFromDate: null,
        selectedUntilDate: null,
        dayCounts: null,
        i: 0,
        minDate: null,
        maxDate: null,
        showMinDate: null,
        showMaxDate: null,
        today: null,
        selectedRooms: [],
        selectedRestrictions: [],
        datesScrollOptions: {
          sizeAutoCapable: true,
          clipAlways: true,
          callbacks: {
            onOverflowChanged: this.resetScroller,
            onOverflowAmountChanged: this.resetScroller,
            onScroll: this.updateScrollButtons,
          },
        },
        scrollLeftEnabled: false,
        scrollRightEnabled: false,

        selectedField: null,
        selectedDay: null,
        periodSet: false,

        quickUpdate: {
          weekdays: [],
          selectedRooms: [],
        },
        quMoreOpened: true,
      };
    },
    async created() {
      this.today = moment();
      this.minDate = moment();
      this.maxDate = moment()
        .add(1, 'month');

      await this.fetchRooms(true);
      if (this.rooms != null) {
        // make all rooms selected
        this.selectedRooms = this.rooms.map(({ id }) => id);
        this.setPeriod();
      }
      // this.resetQuickUpdate();
      // this.$refs.quickUpdateModal.show();
    },
    computed: {
      ...mapGetters('user', ['currency', 'dateFormat']),
      ...mapGetters('calendar', ['roomsLoaded', 'dataLoaded', 'data']),
      ...mapState('calendar', [
        'roomsError', 'roomsPending', 'rooms',
        'dataError', 'pmsError', 'dataPending',
        'dataUpdating', 'dataUpdateError',
        'updatingDay', 'updatedDay',
      ]),
      formQUValid() {
        return this.quickUpdate.selectedRooms.length > 0
          && this.quickUpdate.weekdays.length > 0
          && (this.quHasMainFields || this.quHasMoreFields);
      },
      quHasMainFields() {
        return quMainFields.some((f) => (this.quickUpdate[`do_${f}`]));
      },
      quHasMoreFields() {
        return quMoreFields.some((f) => (this.quickUpdate[`do_${f}`]));
      },
      restrictionOptions() {
        return ['minlos', 'maxlos', 'csale', 'carr', 'cdep'].map((fld, i) => ({
          id: i + 1,
          text: this.$t(`restrictions.${fld}`),
          tip: this.$t(`restrictions.${fld}-tip`),
          fld,
          cb: i > 1,
        }));
      },
      weekdays: {
        get() { return weekdays; },
      },
      allDays: {
        get() {
          return this.quickUpdate.weekdays.length === weekdays.length;
        },
        set(on) {
          this.quickUpdate.weekdays = on ? [...weekdays] : [];
        },
      },
      dates() {
        const acc = [];
        const start = moment(this.showMinDate).locale(moment.locale());
        let obj = {
          month: this.$t('format.datetime', [start, 'pages.calendar.scroller-date-format']).capitalize(),
          days: [],
        };
        while (start.isSameOrBefore(this.showMaxDate, 'date')) {
          const my = this.$t('format.datetime', [start, 'pages.calendar.scroller-date-format']).capitalize();
          if (obj.month !== my) {
            acc.push(obj);
            obj = {
              month: my,
              days: [],
            };
          }
          let w = start.day() - 1;
          if (w < 0) w = 6;
          obj.days.push({
            i: start.format('YYYY-MM-DD'),
            t: start.format('ddd').capitalize(),
            d: start.format('DD'),
            w,
          });
          start.add(1, 'day');
        }
        if (obj.days.length) {
          acc.push(obj);
        }
        return acc;
      },
    },
    methods: {
      ...mapActions('calendar', ['fetchRooms', 'fetchData', 'updateData', 'batchUpdateData']),

      fromChanged(dt) {
        if (dt.isAfter(this.maxDate, 'date')) {
          this.maxDate = moment(dt);
        }
        this.$nextTick(() => {
          this.$refs.dateUntil.$el.focus();
        });
      },
      untilChanged(dt) {
        if (dt.isBefore(this.minDate, 'date')) {
          this.minDate = moment(dt);
        }
      },
      setPeriod() {
        this.selectedField = null;
        this.selectedDay = null;
        this.setPeriodDates();
        this.loadRoomsData();
        this.periodSet = true;
        // get days count
        const start = moment(this.minDate, 'DD-MM-YYYY');
        const end = moment(this.maxDate, 'DD-MM-YYYY');
        this.dayCounts = end.diff(start, 'days');
      },
      setPeriodDates() {
        this.showMinDate = moment(this.minDate);
        this.showMaxDate = moment(this.maxDate);
      },
      loadRoomsData() {
        this.fetchData({
          from: this.minDate.format('YYYY-MM-DD'),
          until: this.maxDate.format('YYYY-MM-DD'),
        });
      },
      isRoomShown(id) {
        return this.selectedRooms.includes(id);
      },
      firstShownRoom(rid) {
        const visible = this.rooms.filter(({ id }) => this.selectedRooms.includes(id));
        return visible.length && visible[0].id === rid;
      },
      isRestrictionShown(id) {
        return this.selectedRestrictions.includes(id);
      },
      lastShownRestriction(rid) {
        const visible = this.restrictionOptions.filter(({ id }) => this.selectedRestrictions.includes(id));
        return visible.length && visible[visible.length - 1].id === rid;
      },
      resetScroller() {
        this.$nextTick(() => {
          const inst = this.$refs.datesHeader.osInstance();
          if (inst == null) return;
          const state = inst.getState();
          if (state.hasOverflow.x) {
            inst.scroll({ x: 0 });
          }
          this.updateScrollButtons();
        });
      },
      updateScrollButtons() {
        const inst = this.$refs.datesHeader.osInstance();
        const scroll = inst.scroll();
        this.scrollLeftEnabled = scroll.position.x > 0;
        this.scrollRightEnabled = scroll.position.x < scroll.max.x;
        (this.$refs.roomRows || []).forEach((r) => {
          (window.requestAnimationFrame || window.mozRequestAnimationFrame || window.webkitRequestAnimationFrame)(
            () => {
              // eslint-disable-next-line no-param-reassign
              r.scrollLeft = scroll.position.x;
            },
          );
        });
      },
      scrollLeft(type) {
        const inst = this.$refs.datesHeader.osInstance();
        if (type === 'double') {
          inst.scroll({ x: '-=14 * 48' }, 350);
        } else {
          inst.scroll({ x: '-=7 * 48' }, 350);
        }
      },
      scrollRight(type) {
        const inst = this.$refs.datesHeader.osInstance();
        if (type === 'double') {
          inst.scroll({ x: '+=14 * 48' }, 350);
        } else {
          inst.scroll({ x: '+=7 * 48' }, 350);
        }
      },
      focusField(ev) {
        const dd = ev.target;
        const input = dd.querySelector('input');
        if (input == null || input.disabled) return;
        input.focus();
      },
      selectField(ev, day) {
        const dd = ev.target.parentNode;
        this.selectedDay = day;
        this.selectedField = dd.parentNode.getAttribute('data-field');
        dd.classList.add('selected');
        ev.target.setSelectionRange(0, 99);
      },
      deselectField(ev) {
        const original = this.selectedDay[this.selectedField];
        this.selectedDay = null;
        this.selectedField = null;
        const fld = ev.target;
        if (fld.value !== original) {
          fld.value = original;
          fld.dispatchEvent(new Event('input'));
        }
        fld.parentNode.classList.remove('selected');
      },
      resetField(ev) {
        ev.target.blur();
      },
      checkFieldValue(field, value, day = null) {
        const i = /^\d+$/.test(value);
        const f = /^\d+(\.\d\d?)?$/.test(value);
        switch (field) {
          case 'avail':
            return i && value >= 0 && value <= 999;
          case 'price':
            return f && value >= 0.01 && value <= 999999999.99;
          case 'minlos':
            return i && value >= 1 && value <= 999 && value <= day.maxlos;
          case 'maxlos':
            return i && value >= 1 && value <= 999 && value >= day.minlos;
          default:
            return true;
        }
      },
      updateField(ev, id, selDay, selField) {
        const cb = typeof ev === 'boolean';
        const value = cb ? ev : ev.target.value;
        const fld = !cb ? ev.target : null;
        const dayObject = selDay != null ? selDay : this.selectedDay;
        const field = selField != null ? selField : this.selectedField;
        const original = dayObject[field];
        const day = dayObject.i;
        if (value === original || (!cb && !`${value}`.trim()) || !this.checkFieldValue(field, value, dayObject)) {
          if (!cb) fld.blur();
          return;
        }
        let data;
        let mode;
        if (field === 'avail') {
          mode = 'avail';
          data = {
            avail: value,
          };
        } else {
          mode = 'data';
          data = {
            price: formatNumber(dayObject.price),
            minlos: dayObject.minlos,
            maxlos: dayObject.maxlos,
            csale: dayObject.csale,
            carr: dayObject.carr,
            cdep: dayObject.cdep,
          };
          data[field] = value;
        }
        if (!cb) {
          this.$nextTick(() => {
            fld.blur();
          });
        }
        this.updateData({
          mode,
          id,
          day,
          field,
          data,
        });
      },
      resetQuickUpdate() {
        this.quickUpdate = {
          from: moment(this.minDate),
          until: moment(this.maxDate),
          weekdays: [...weekdays],
          selectedRooms: [],
          cdep: false,
          carr: false,
          grnt: 3,
          osale: true,
        };
        this.quMoreOpened = false;
        this.$refs.quickUpdateForm.reset();
      },
      fromQUChanged(dt) {
        if (dt.isAfter(this.quickUpdate.until, 'date')) {
          this.quickUpdate.until = moment(dt);
        }
        this.$nextTick(() => {
          this.$refs.dateQUUntil.$el.focus();
        });
      },
      untilQUChanged(dt) {
        if (dt.isBefore(this.quickUpdate.from, 'date')) {
          this.quickUpdate.from = moment(dt);
        }
      },
      rulesFor(field) {
        const on = this.quickUpdate[`do_${field}`];
        if (!on) return {};
        const rules = {
          required: true,
        };
        switch (field) {
          case 'avail':
            rules.between = { min: 0, max: 999 };
            rules.numeric = true;
            break;
          case 'price':
            rules.between = { min: 0.01, max: 999999999.99 };
            break;
          case 'minlos':
            rules.between = { min: 1, max: 999 };
            rules.numeric = true;
            if (this.quickUpdate.do_maxlos) {
              rules.maxvalue = this.quickUpdate.maxlos;
            }
            break;
          case 'maxlos':
            rules.between = { min: 1, max: 999 };
            rules.numeric = true;
            if (this.quickUpdate.do_minlos) {
              rules.minvalue = this.quickUpdate.minlos;
            }
            break;
          default:
            break;
        }
        return rules;
      },
      async processQuickUpdate() {
        const payload = {
          from: this.quickUpdate.from.format('YYYY-MM-DD'),
          until: this.quickUpdate.until.format('YYYY-MM-DD'),
          rooms: [...this.quickUpdate.selectedRooms],
          days: [...this.quickUpdate.weekdays],
          fields: {},
        };
        quMainFields.concat(quMoreFields)
          .forEach((f) => {
            if (this.quickUpdate[`do_${f}`]) {
              payload.fields[f] = this.quickUpdate[f];
            }
          });
        const result = await this.batchUpdateData(payload);
        if (result) {
          this.$refs.quickUpdateModal.hide();
        } else {
          /* eslint-disable-next-line no-console */
          console.error(this.dataUpdateError);
        }
      },
      isDayUpdating(rid, day, field) {
        return this.updatingDay === `${field} ${rid} ${day}`;
      },
      isDayUpdated(rid, day, field) {
        return this.updatedDay === `${field} ${rid} ${day}`;
      },
      currencySymbol() {
        return currencySymbols[this.currency];
      },
    },
  };
</script>
