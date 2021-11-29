<template>
  <div class="component-hotels">
    <portal to="modals">
      <b-modal
        no-close-on-backdrop
        id="importHotelsModal"
        ref="importModal"
        no-fade
        centered
        static
        modal-class="form-modal"
        :ok-title="$t('buttons.import')"
        ok-variant="primary"
        :cancel-title="$t('buttons.cancel')"
        cancel-variant="outline-primary"
        :ok-disabled="pending || !$refs.importForm || !importFormValid || !$refs.importForm.flags.valid"
        :cancel-disabled="pending"
        :no-close-on-esc="pending"
        :hide-header-close="pending"
        @show="modalScroll"
        @hidden="modalScroll"
        @ok.prevent="processInviteForm"
      >
        <template #modal-header-close>
          <icon width="20" height="20" class="d-none d-md-block" type="times"/>
          <icon width="10" height="18" class="d-md-none" type="arrow-left"/>
        </template>
        <template #modal-title>
          {{ $t('pages.hotels.modal.title-import') }}
        </template>
        <ValidationObserver ref="importForm" slim>
          <div class="row">
            <div class="col-12">
              <label class="text-xs">{{ $t('pages.hotels.modal.field-import') }}</label>
              <ValidatedField type="textarea" name="import" :disabled="pending"
                              no-icon no-tooltip no-validate rules=""
                              v-model.trim="importHotels.text" :rows="15" class="text-monospace" />
            </div>
            <div class="col-12">
              <span :class="importHotels.ids.length ? 'text-success' : 'text-danger'">
                {{ $tc('pages.hotels.modal.found-ids', importHotels.ids.length) }}
              </span>
            </div>
          </div>
        </ValidationObserver>
      </b-modal>

      <b-modal
        no-close-on-backdrop
        id="hotelsModal"
        ref="modal"
        no-fade
        centered
        static
        modal-class="form-modal"
        ignore-enforce-focus-selector=".ss-search > input"
        :ok-title="$t(`buttons.${hotel.id != null ? 'update' : 'save'}`)"
        ok-variant="primary"
        :cancel-title="$t('buttons.cancel')"
        cancel-variant="outline-primary"
        :ok-disabled="pending || !$refs.form || !formValid || !$refs.form.flags.valid"
        :cancel-disabled="pending"
        :no-close-on-esc="pending"
        :hide-header-close="pending"
        @show="modalScroll"
        @hidden="modalScroll"
        @ok.prevent="processForm"
        v-if="loaded"
      >
        <template #modal-header-close>
          <icon width="20" height="20" class="d-none d-md-block" type="times"/>
          <icon width="10" height="18" class="d-md-none" type="arrow-left"/>
        </template>
        <template #modal-title>
          {{ modalTitle }}
        </template>
        <ValidationObserver ref="form" slim>
          <div class="row">
            <div class="col-12">
              <label class="text-xs">{{ $t('addr.property-or-hotel-name') }}</label>
              <ValidatedField name="hotel-name" :disabled="pending" rules="required|max:255" v-model.trim="hotel.name"/>
            </div>
          </div>
          <div class="row">
            <div class="col-12">
              <label class="text-xs">{{ $t('addr.street') }}</label>
              <ValidatedField name="street" :disabled="pending" rules="required|max:255" v-model.trim="hotel.street"/>
            </div>
          </div>
          <div class="row">
            <div class="col-12 col-md-6">
              <label class="text-xs">{{ $t('addr.phone') }}</label>
              <ValidatedField name="tel" type="tel" :disabled="pending"
                              rules="required|max:20" v-model.trim="hotel.tel"/>
            </div>
            <div class="col-12 col-md-6">
              <label class="text-xs">{{ $t('addr.zip') }}</label>
              <ValidatedField name="zip" type="text"
                              :disabled="pending" rules="required_string|max:10" v-model.trim="hotel.zip"/>
            </div>
          </div>
          <div class="row">
            <div class="col-12 col-md-6">
              <label class="text-xs">{{ $t('addr.city') }}</label>
              <ValidatedField name="city" :disabled="pending" rules="required|max:255" v-model.trim="hotel.city"/>
            </div>
            <div class="col-12 col-md-6">
              <label class="text-xs">{{ $t('addr.country') }}</label>
              <ValidatedField name="country" :disabled="pending || countries==null" v-model.trim="hotel.country"
                              type="select" track-by="code" label-by="name" :options="countries"
                              rules="required" searchable/>
            </div>
          </div>
          <div class="row">
            <div class="col-12 col-md-6">
              <label class="text-xs">{{ $t('pages.masterdata.primary-email') }}</label>
              <ValidatedField name="email" :disabled="pending" rules="required|email|max:255"
                              v-model.trim="hotel.email"/>
            </div>
            <div class="col-12 col-md-6" v-if="loadedCurrencies">
              <label class="text-xs">{{ $t('pages.masterdata.choose-currency') }}</label>
              <ValidatedField name="country" :disabled="pending || allCurrencies==null"
                              v-model.trim="hotel.currency_code"
                              type="select" track-by="code" label-by="text" :options="allCurrencies"
                              rules="required" searchable/>
            </div>
          </div>

          <!--
          <div class="row">
            <div class="col-12">
              <label class="text-xs">{{ $t('pages.masterdata.website') }}</label>
              <ValidatedField rules="required_string|url" :disabled="pending"
                              name="website" v-model.trim="hotel.website"/>

            </div>
          </div>
          -->
        </ValidationObserver>
      </b-modal>

      <booking-status-confirm-modal ref="confirmModal" @ok="changeStatus" :pending="pending" />

    </portal>

    <div class="position-relative panel-content" :class="{ panel: !admin }">
      <div class="list d-none d-md-block" :class="{ 'left-edge': !admin }">
        <p class="head-line justify-content-between">
          <span>{{ heading }}<spinner v-if="!loaded" /></span>
          <search-filter v-model="filter" :disabled="!loaded || pending" :placeholder="$t('pages.hotels.filter-tip')" />
        </p>
        <div class="hotels-table" v-if="loaded">
          <table class="w-100">
            <thead>
              <tr>
                <th class="w-id">
                  <div>
                    {{ $t('id') }}
                    <sort-indicator v-model="sort" field="id" />
                  </div>
                </th>
                <th class="w-name">
                  <div>
                    {{ $t('pages.hotels.headers.name') }}
                    <sort-indicator v-model="sort" field="name" />
                  </div>
                </th>
                <th class="w-name">
                  <div>
                    {{ $t('addr.country') }}
                    <sort-indicator v-model="sort" field="country" />
                  </div>
                </th>
                <th class="w-name">
                  <div>
                    {{ $t('pages.hotels.headers.currency') }}
                    <sort-indicator v-model="sort" field="currency.code" />
                  </div>
                </th>
                <th class="w-name">
                  <div>
                    {{ $t('pages.hotels.headers.create-date') }}
                    <sort-indicator v-model="sort" field="created_at" />
                  </div>
                </th>
                <th class="w-name">
                  <div>
                    {{ $t('pages.hotels.headers.status') }}
                    <sort-indicator v-model="sort" field="active" />
                  </div>
                </th>
                <th class="w-actions">{{ $t('actions') }}</th>
              </tr>
            </thead>

            <tbody v-if="!hasHotels">
              <tr>
                <td colspan="6" class="w-empty">{{ $t('pages.hotels.no-hotels') }}</td>
              </tr>
            </tbody>
            <tbody v-if="filter && !filteredHotels.length">
              <tr>
                <td colspan="6" class="w-empty">{{ $t('pages.hotels.filter-no-hotels') }}</td>
              </tr>
            </tbody>
            <tbody v-for="row in filteredHotels" :key="row.id">
              <tr class="separator before"></tr>
              <tr>
                <td>
                  {{ row.id }}
                </td>
                <td>
                  <p>{{ row.name }}</p>
                </td>
                <td>
                  <p>{{ getCountryName(row.country) }}</p>
                </td>
                <td>
                  <p>{{ row.currency_code }}</p>
                </td>
                <td>
                  <p>{{ createDate(row) }}</p>
                </td>
                <td>
                  <switcher :checked="row.active" colored lazy
                            :on-label="$t('pages.booking.on')" :off-label="$t('pages.booking.off')"
                            @willChange="showStatusConfirm(row, $event)" :disabled="pending" />
                </td>
                <td class="actions">
                  <b-btn class="btn-icon btn-tiny" @click="editHotel(row.id)" :disabled="pending">
                    <icon width="17" height="17" type="edit"/>
                  </b-btn>
                </td>
              </tr>
              <tr class="separator after"></tr>
            </tbody>
          </table>
        </div>
        <div v-if="loaded" class="d-flex justify-content-between">
          <b-btn pill variant="outline-primary" class="add-new-hotel"
                 @click="openCreateForm">
            <icon width="10" height="11" type="plus"/>
            {{ $t('pages.hotels.button-add') }}
          </b-btn>
          <!--          <b-btn pill variant="outline-primary" class="add-new-hotel" v-if="admin"-->
          <!--                 @click="openImportForm">-->
          <!--            <icon width="10" height="11" type="plus"/>-->
          <!--            {{ $t('pages.hotels.button-import') }}-->
          <!--          </b-btn>-->
        </div>
      </div>

      <p class="head-line d-md-none justify-content-between" v-if="!isMobileSearch">
        <span class="d-md-none">{{ heading }}</span>
        <spinner v-if="!loaded" />
        <icon @click="mobileSearch" width="20" height="20" type="search"/>
      </p>
      <search-filter class="d-md-none"
                     v-model="filter" :disabled="!loaded || pending"
                     :placeholder="$t('pages.hotels.filter-tip')"
                     v-if="isMobileSearch" />
      <div class="d-md-none list-item" v-if="!hasHotels">
        <div class="w-empty">{{ $t('pages.hotels.no-hotels') }}</div>
      </div>
      <div class="d-md-none list-item" v-for="row in filteredHotels" :key="row.id">
        <div class="d-flex line">
          <div class="w-50">
            <p class="label">{{ $t('id') }}</p>
            <p>{{ row.id }}</p>
          </div>
          <div class="w-50">
            <p class="label">{{ $t('pages.hotels.headers.name') }}</p>
            <p>{{ row.name }}</p>
          </div>
          <div class="w-25 text-right">
            <b-dropdown size="sm" toggle-tag="span" variant="link" no-caret right :disabled="pending">
              <template #button-content>
                <icon width="20" height="19" class="label" type="dots-h"/>
              </template>
              <b-dropdown-item @click="editHotel(row.id)">{{ $t('buttons.edit') }}</b-dropdown-item>
              <b-dropdown-item @click="toggleStatus(row)">
                {{ $t(`pages.hotels.button-${row.active?'de':''}activate`) }}
              </b-dropdown-item>
            </b-dropdown>
          </div>
        </div>
        <div class="d-flex line">
          <div class="w-50">
            <p class="label">{{ $t('addr.country') }}</p>
            <p>{{ getCountryName(row.country) }}</p>
          </div>
          <div class="w-50">
            <p class="label">{{ $t('pages.hotels.headers.currency') }}</p>
            <p>{{ row.currency_code }}</p>
          </div>
          <div class="w-25"></div>
        </div>
        <div class="d-flex line">
          <div class="w-50">
            <p class="label">{{ $t('pages.hotels.headers.create-date') }}</p>
            <p>{{ createDate(row) }}</p>
          </div>
          <div class="w-50">
            <p class="label">{{ $t('pages.hotels.headers.status') }}</p>
            <switcher :checked="row.active" colored lazy
                      :on-label="$t('pages.booking.on')" :off-label="$t('pages.booking.off')"
                      @willChange="showStatusConfirm(row, $event)" :disabled="pending" />
          </div>
          <div class="w-25"></div>
        </div>
      </div>
    </div>
    <div class="d-md-none add-new-hotel-block d-flex" v-if="loaded">
      <b-btn pill variant="outline-primary" class="add-new-hotel" @click="openCreateForm">
        <icon width="10" height="10" type="plus"/>
        {{ $t('pages.hotels.button-add') }}
      </b-btn>
      <!--      <b-btn v-if="admin" pill variant="outline-primary" class="add-new-hotel" @click="openImportForm">-->
      <!--        <icon width="10" height="10" type="plus"/>-->
      <!--        {{ $t('pages.hotels.button-import') }}-->
      <!--      </b-btn>-->
    </div>
  </div>
</template>

<script>
  import moment from 'moment';
  import { mapActions, mapGetters, mapState } from 'vuex';
  import BookingStatusConfirmModal from '@/components/BookingStatusConfirmModal.vue';
  import { pick } from '@/helpers';

  export default {
    name: 'Hotels',
    components: { BookingStatusConfirmModal },
    props: {
      payload: {
        validator: (v) => ['object', 'undefined'].includes(typeof v),
        default: null,
      },
      asAdmin: {
        type: Boolean,
        default: false,
      },
    },
    data: () => ({
      hotel: {},
      importHotels: {
        text: '',
        ids: [],
      },
      filter: '',
      sort: '+id',
      isMobileSearch: false,
    }),
    async created() {
      this.resetModal();
      const tasks = [
        await this.fetchHotels(),
        await this.fetchCountries(),
        await this.fetchCurrencies(),
      ];
      await Promise.allSettled(tasks);
      // document.querySelector('.add-new-hotel').click();
    },
    watch: {
      'importHotels.text': function watchImportText() {
        const ids = this.importHotels.text
          .replace(/\D+/mg, ' ')
          .replace(/\s+/mg, ' ')
          .split(' ')
          .filter((id) => id.length > 2);
        const uniq = new Set(ids);
        this.importHotels.ids = Array.from(uniq);
      },
    },
    computed: {
      ...mapGetters('data', ['countries', 'currencies', 'loadedCurrencies']),
      ...mapGetters('user', ['isAdmin', 'lang']),
      ...mapGetters('user', {
        currentGroup: 'group',
        userHotels: 'hotels',
        userHasHotels: 'hasHotels',
      }),
      ...mapGetters('group', {
        groupHotelsLoaded: 'loaded',
        groupHotels: 'hotels',
        groupHasHotels: 'hasHotels',
      }),
      ...mapState('user', {
        userHotelsPending: 'hotelsPending',
        userHotelsLoaded: 'hotelsLoaded',
      }),
      ...mapState('group', {
        groupHotelsPending: 'hotelsPending',
      }),
      loaded() {
        return this.admin ? this.groupHotelsLoaded : this.userHotelsLoaded;
      },
      hotels() {
        return this.admin ? this.groupHotels : this.userHotels;
      },
      pending() {
        return this.admin ? this.groupHotelsPending : this.userHotelsPending;
      },
      hasHotels() {
        return this.admin ? this.groupHasHotels : this.userHasHotels;
      },
      admin() {
        return this.asAdmin && this.isAdmin;
      },
      heading() {
        return this.admin ? this.$t('pages.hotels.heading-admin') : this.$t('pages.hotels.heading');
      },
      allCurrencies() {
        return this.currencies.data.result.map(({ id, name, code }) => ({ id, text: `${name} (${code})`, code }));
      },
      filteredHotels() {
        const filter = this.filter.trim().toLowerCase();
        let ret = [...(this.admin ? this.groupHotels : this.hotels)];
        if (filter) {
          ret = ret.filter(({ id, name }) => (`${id}`.includes(filter) || name.toLowerCase().includes(filter)));
        }
        const field = this.sort.substr(1);
        const k = this.sort.charAt(0) === '+' ? 1 : -1;
        ret = ret.sort((a, b) => {
          const v1 = pick(a, field);
          const v2 = pick(b, field);
          // eslint-disable-next-line no-nested-ternary
          return k * (v1 > v2 ? 1 : (v1 < v2 ? -1 : 0));
        });
        ret.forEach((hotel, index) => {
          if (this.loadedCurrencies) {
            const result = this.currencies.data.result.filter((obj) => obj.code === hotel.currency_code);
            if (result.length > 0) {
              const updatedCurrency = {
                currency: {
                  code: result[0].code,
                  symbol: result[0].code,
                },
              };
              ret[index] = { ...ret[index], ...updatedCurrency };
            }
          }
        });
        return ret;
      },
      formValid() {
        return true;
      },
      importFormValid() {
        return this.importHotels.ids.length > 0;
      },
      modalTitle() {
        if (this.hotel.id == null) {
          return this.$t('pages.hotels.modal.title-add');
        }
        const { id, name } = this.hotel;
        return this.$t('pages.hotels.modal.title-edit', { id, name });
      },
    },
    methods: {
      ...mapActions('data', ['fetchCountries', 'fetchCurrencies']),
      ...mapActions('user', ['loadHotels', 'getHotel', 'createHotel', 'updateHotel', 'toggleHotelStatus']),
      ...mapActions('group', {
        getGroupHotel: 'getHotel',
        createGroupHotel: 'createHotel',
        updateGroupHotel: 'updateHotel',
        toggleGroupHotelStatus: 'toggleHotelStatus',
        importGroupHotel: 'importHotel',
      }),
      ...mapActions('groups', ['getGroups']),

      async fetchHotels() {
        if (!this.admin) {
          await this.loadHotels();
        }
      },

      modalScroll(ev) {
        const modal = ev.target;
        this.$nextTick(() => {
          if (modal != null) modal.scrollTop = 0;
        });
      },
      resetImportModal() {
        this.importHotels = {
          text: '',
          ids: [],
        };
        this.$refs.importForm.reset();
      },
      resetModal() {
        this.hotel = {
          name: '',
          street: '',
          tel: '',
          zip: '',
          city: '',
          country: '',
          email: '',
          group_id: null,
          lang: this.lang.code,
          currency_code: '',
          // altemail: '',
          // capacity: 0,
          // capmode: 0,
          // website: '',
        };
        if (this.$refs.form != null) {
          this.$refs.form.reset();
        }
      },
      createDate(row) {
        return moment(row.created_at).format(this.$t('pages.hotels.headers.create-date-format'));
      },
      showStatusConfirm(row, futureStatus) {
        this.$refs.confirmModal.show(futureStatus, row.id);
      },
      async toggleStatus(row) {
        await this.changeStatus({ status: !row.active, payload: row.id });
      },
      async changeStatus({ status: active, payload: id }) {
        try {
          if (this.admin) {
            await this.toggleGroupHotelStatus({ id, active });
          } else {
            await this.toggleHotelStatus({ id, active });
          }
          this.$refs.confirmModal.hide();
        } catch (error) {
          this.$toastr.e(error.message, this.$t('error'));
        }
      },
      openImportForm() {
        this.resetImportModal();
        this.$nextTick(this.$refs.importModal.show);
      },
      openCreateForm() {
        this.resetModal();
        this.$nextTick(this.$refs.modal.show);
      },
      async editHotel(id) {
        try {
          const data = this.admin ? await this.getGroupHotel(id) : await this.getHotel(id);
          delete data.capacity;
          delete data.capacity_mode;
          this.resetModal();
          const record = this.hotels.find((h) => h.id === id);
          // eslint-disable-next-line camelcase
          const { group_id } = record;
          this.hotel = {
            id,
            ...this.hotel,
            group_id,
            ...JSON.parse(JSON.stringify(data)), // deep clone without bindings
          };
          this.$nextTick(this.$refs.modal.show);
        } catch (error) {
          this.$toastr.e(error.message, this.$t('error'));
        }
      },
      async processForm() {
        const userHasHotelsBeforeProcessing = this.userHasHotels;
        const { code: lang } = this.lang;
        const partial = true;

        const hotel = { ...this.hotel, lang, partial };
        if (this.payload != null) {
          Object.keys(this.payload).forEach((k) => {
            hotel[k] = this.payload[k];
          });
        }
        const upd = this.admin ? this.updateGroupHotel : this.updateHotel;
        const crt = this.admin ? this.createGroupHotel : this.createHotel;
        try {
          if (hotel.id != null) {
            await upd(hotel);
          } else {
            await crt({
              ...hotel,
              group_id: this.currentGroup?.id,
            });
          }
          this.$refs.modal.hide();
          if (!userHasHotelsBeforeProcessing) window.location.reload();
        } catch (error) {
          this.$toastr.e(error.message, this.$t('error'));
          // throw error;
        }
      },
      async processInviteForm() {
        const ids = [...this.importHotels.ids];
        // eslint-disable-next-line promise/no-nesting
        await ids.reduce((prm, id) => prm.then(() => this.importGroupHotel(id)
          .then(() => this.$toastr.s(this.$t('pages.hotels.modal.import-done', { id }), id))
          .catch((e) => this.$toastr.e(e.message, id))), Promise.resolve());
        this.$refs.importModal.hide();
      },
      getCountryName(code) {
        const searchedCountry = this.countries.find((country) => country.code === code);
        return searchedCountry ? searchedCountry.name : '';
      },
      mobileSearch() {
        this.isMobileSearch = !this.isMobileSearch;
      },
    },
  };
</script>
