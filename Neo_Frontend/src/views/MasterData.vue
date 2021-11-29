<template>
  <div class="page-masterdata">
    <div class="">
      <div ref="title" class="panel-title position-relative w-100 title">
        <p>{{ $t('pages.masterdata.title') }}</p>
      </div>
      <b-alert v-if="pmsError" variant="danger" show>
        <h4 class="alert-heading">{{ $t('error') }}</h4>
        <p class="mb-0">{{ pmsError.response ? pmsError.response.data.message : pmsError }}</p>
      </b-alert>
      <div v-else id="tabs">
        <div class="tabs" @click="initProperty">
          <a v-for="(tab, idx) in tabs" :key="`tab-${idx}`" @click="setTab(idx)"
             :class="{ active: activetab === idx }">
            {{ $t(tab) }}
          </a>
        </div>
        <div class="">
          <div v-show="activetab === 0" class="tab-content">
            <ValidationObserver ref="form0">
              <form @submit.prevent="onSubmit">
                <div class="row">
                  <div class="col-12">
                    <ImageSelector v-model="logo" />
                  </div>
                </div>
                <div class="row">
                  <p class="col-12 head-line">
                    {{ $t('pages.masterdata.property-name') }}<span class="required">*</span>
                  </p>
                </div>
                <div class="row">
                  <div class="col-md-6 col-sm">
                    <ValidatedField rules="required_string|max:100" :disabled="pending"
                                    type="text" name="name" :placeholder="$t('pages.masterdata.property-name')"
                                    v-model="property.name"
                    />
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-6 col-sm input-padding-left">
                    <p class="head-line">{{ $t('pages.masterdata.property-type') }}</p>
                    <drop-down
                      id="type"
                      id-key="id"
                      label-key="name"
                      rules="required"
                      :items="allTypes"
                      :disabled="pending"
                      v-model="property.type"
                    />
                  </div>
                </div>
                <div class="row">
                  <p class="col-12 head-line">{{ $t('pages.masterdata.capacity') }}<span class="required">*</span></p>
                </div>
                <div class="row">
                  <div class="col-md-2 col-sm  input-padding-right capacity">
                    <ValidatedField rules="required|between:1,9999" :disabled="pending" autocomplete="no"
                                    type="number" name="capacity" min="1" max="9999"
                                    v-model="property.capacity"
                    />
                  </div>

                  <div class="col cell-qu-edit-fields">
                    <radio v-model="property.capacity_mode" :val="0" :disabled="pending"
                           name="capacity_mode">{{ $t('pages.masterdata.capacity-rooms') }}</radio>
                    <radio v-model="property.capacity_mode" :val="1" :disabled="pending"
                           name="capacity_mode">{{ $t('pages.masterdata.capacity-beds') }}</radio>
                  </div>
                </div>
                <div class="row ">
                  <p class="col-12 head-line">
                    {{ $t('pages.masterdata.property-tel') }}<span class="required">*</span>
                    <spinner v-if="verifyingTel"/>
                  </p>
                </div>
                <div class="row">
                  <div class="col-md-3 col-sm">
                    <ValidatedField group-class="" @blur="validatePhone()"
                                    name="tel" mode="lazy" type="text"
                                    autocomplete="tel" v-model.trim="property.tel" :disabled="pending"
                                    :rules="telRules" :error-bag="validationError"/>
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-5 col-sm input-padding-right">
                    <p class="head-line ">{{ $t('pages.masterdata.primary-email') }}<span class="required">*</span></p>
                    <ValidatedField rules="required|email" placeholder="name@company.com"
                                    type="email" name="email" v-model.trim="property.email"
                                    autocomplete="email" class="mb-2" :error-bag="validationError" :disabled="pending"/>
                    <p class="forMail">{{ $t('pages.masterdata.primary-email-tip') }}</p>
                  </div>
                  <!-- <div class="col-md-3 col-sm input-padding-left">
                    <p class="head-line">{{ $t('pages.masterdata.alt-email') }}</p>
                    <ValidatedField rules="required|email" placeholder="name@company.com"
                                    type="email" name="alternaitve_email"
                                    v-model.trim="property.alternative_email" autocomplete="email"
                                    class="mb-2" :error-bag="validationError" :disabled="pending"/>
                    <p class="forMail">{{ $t('pages.masterdata.alt-email-tip') }}</p>
                  </div> -->
                </div>
                <div class="row">
                  <p class="col-12 head-line">{{ $t('pages.masterdata.website') }}<span class="required">*</span></p>
                </div>
                <div class="row">
                  <div class="col-md-6 col-sm">
                    <ValidatedField rules="required_string|url" :disabled="pending"
                                    type="text" name="website_name" :placeholder="$t('pages.masterdata.website')"
                                    v-model="property.website"
                    />
                  </div>
                </div>
                <div class="row">
                  <div class="col-12">
                    <p
                      class="head-line"
                      :class="(property.hasMapped) ? 'opacity-50' : ''">{{ $t('pages.masterdata.choose-currency') }}</p>
                  </div>
                </div>
                <div class="row currency">
                  <div class="col-md-3 col-10" v-if="loadedCurrencies">
                    <drop-down
                      id="currency"
                      id-key="code"
                      label-key="text"
                      :items="allCurrencies"
                      :disabled="pending || property.hasMapped"
                      v-model="property.currency_code"
                    />
                  </div>
                  <div class="col-md-3 col-2" v-if="property.hasMapped">
                    <icon
                      class="icon-info"
                      :class="(currencyModal == 1) ? 'opacity-50' : ''"
                      @click="toggleCurrencyModal(1)"
                      width="25"
                      height="25"
                      type="info"/>
                  </div>
                  <div class="col-12" v-if="currencyModal">
                    <div class="modal-info">
                      <icon class="icon-info" width="25" height="25" type="info"/>
                      <p>{{ $t('pages.masterdata.currency-text') }}</p>
                      <icon class="close-info" @click="toggleCurrencyModal(0)" width="25" height="25" type="close"/>
                    </div>
                  </div>
                </div>
              </form>
            </ValidationObserver>
          </div>
          <div v-show="activetab === 1" class="tab-content">
            <ValidationObserver ref="form1">
              <form @submit.prevent="onSubmit">
                <div class="row">
                  <p class="col-12 head-line">{{ $t('addr.street') }}<span class="required">*</span></p>
                </div>
                <div class="row">
                  <div class="col-md-6 col-sm">
                    <ValidatedField rules="required_string|max:100" :disabled="pending"
                                    type="text" name="street_no" :placeholder="$t('addr.street')"
                                    v-model="address.street"
                    />
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-6 col-sm">
                    <ValidatedField rules="max:100" :disabled="pending"
                                    type="text" name="second_address" :placeholder="$t('addr.street')"
                                    v-model="address.street_optional"
                    />
                  </div>
                </div>
                <div class="row">
                  <div class="col-12">
                    <div class="row">
                      <div class="col-md-3 col-sm  input-padding-right">
                        <p class="head-line ">{{ $t('addr.zip') }}<span class="required">*</span></p>
                        <ValidatedField rules="required_string|max:10" :disabled="pending"
                                        type="text" name="post_code" :placeholder="$t('addr.zip')"
                                        v-model.trim="address.zip"
                        />
                      </div>
                      <div class="col-md-3 col-sm input-padding-left">
                        <p class="head-line">{{ $t('addr.city') }}<span class="required">*</span></p>
                        <ValidatedField rules="required_string|max:100" :disabled="pending"
                                        type="text" name="city" :placeholder="$t('addr.city')"
                                        v-model="address.city"
                        />
                      </div>
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-6 col-sm input-padding-left">
                    <p class="head-line">{{ $t('addr.country') }}<span class="required">*</span></p>
                    <drop-down
                      id="country"
                      id-key="code"
                      label-key="name"
                      :items="countries"
                      :disabled="pending"
                      v-model="address.country"
                    />
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-6 col-sm input-padding-left">
                    <p class="head-line">{{ $t('addr.state-or-province') }}</p>
                    <drop-down
                      id="state"
                      id-key="name"
                      label-key="name"
                      rules="required"
                      :items="states"
                      :disabled="pending"
                      v-model="address.state"
                    />
                  </div>
                </div>
                <div class="row">
                  <p class="col-12 head-line">{{ $t('pages.masterdata.geodata') }}</p>
                </div>
                <div class="row">
                  <div class="col-md-3 col-sm input-padding-right">
                    <ValidatedField rules="between:-90,90" placeholder="-"
                                    type="text" name="latitude" no-icon no-tooltip class="mb-2"
                                    v-model.trim="address.latitude" :disabled="pending"
                    />
                    <p class="forMail">{{ $t('pages.masterdata.latitude') }}</p>
                  </div>
                  <div class="col-md-3 col-sm input-padding-left">
                    <ValidatedField rules="between:-180,180"
                                    type="text" name="longitude" no-icon no-tooltip class="mb-2"
                                    placeholder="-" :disabled="pending"
                                    v-model.trim="address.longitude"
                    />
                    <p class="forMail">{{ $t('pages.masterdata.longitude') }}</p>
                  </div>
                  <div class="col-md-2 col-sm">
                    <b-button link block variant="primary"
                              :disabled="invalidGeoData"
                              target="_blank"
                              :href="locationUrl">
                      {{ $t('pages.masterdata.button-show-location') }}
                    </b-button>
                  </div>
                </div>
                <div class="row">
                  <span class=" col-12 forMail">{{ $t('pages.masterdata.geodata-tip') }}
                    <a href="https://www.revilodesign.de/tools/google-maps-latitude-longitude-finder/"
                       target="_blank">revilodesign.de</a></span>
                </div>
              </form>
            </ValidationObserver>
          </div>
          <div v-show="activetab === 2" class="tab-content">
            <ValidationObserver ref="form2">
              <form @submit.prevent="onSubmit">
                <div
                  v-for="(identifier, i) in identifiers"
                  :key="identifier.id"
                  class="row"
                >
                  <p class="col-12 head-line">{{ identifier.name }}</p>
                  <div class="col-md-6 col-sm">
                    <ValidatedField
                      rules="max:100"
                      type="text" :name="identifier.abbreviation" no-icon no-tooltip
                      v-model="identifiers[i].value" :disabled="pending"
                    />
                  </div>
                </div>
              </form>
            </ValidationObserver>
          </div>
        </div>
      </div>
      <div class="panel-footer" v-if="activetab !== 3">
        <div class="col-md-3 col-12 align-self-end cell-button">
          <SubmitButton type="button" :disabled="formInvalid" v-if="activetab >= 0"
                        :loading="pending" @click="onSubmit"
          >{{ $t('buttons.save') }}</SubmitButton>
        </div>
      </div>
    </div>
  </div>
</template>
<script>
  import { mapActions, mapGetters, mapState } from 'vuex';

  export default {
    name: 'MasterData',
    data() {
      return {
        activetab: -1,
        tabs: [
          'pages.masterdata.tabs.property',
          'pages.masterdata.tabs.address',
          'pages.masterdata.tabs.ids',
        ],
        property: {},
        address: {},
        hotelData: null,
        currencyModal: 0,
        logo: null,
        identifiers: [],
        verifyingTel: false,
        certificates: {
          0: {
            name: '3 days before arrival',
            uploadDate: '12 Jan 2020',
            value: 4,
            issuedBy: '2 Jan 2020',
          },
          1: {
            name: '3 days before arrival',
            uploadDate: '12 Jan 2020',
            value: 5,
            issuedBy: '2 Jan 2020',
          },
        },
      };
    },
    watch: {
      user: function watchUser(userData) {
        if (userData) {
          this.updateUserData();
        }
      },
      'address.country': {
        handler(newVal, oldVal) {
          if ((newVal.length !== 0) && (newVal !== oldVal)) {
            this.fetchStates({
              country_id: newVal,
            });
          }
        },
      },
    },
    async created() {
      await this.getRoomDBPropertyId();
      // await this.getUser();
      await Promise.allSettled([
        this.fetchCountries(),
        this.fetchPropertyTypes(),
        this.fetchCurrencies(),
        this.getDescription(),
        this.getHotel().then((data) => {
          this.hotelData = data;
          return true;
        }),
        this.getIdentifierSources().then(async (identifierSources) => {
          this.identifiers = identifierSources;
          const identifiersData = await this.getPropertyIdentifiers();
          identifiersData.forEach((data) => {
            const identifier = this.identifiers.find((item) => item.id === data.source.id);
            if (!identifier) return;
            identifier.value = data.identifier;
          });
          return null;
        }),
      ]);
      this.updateUserData();
      // this.validatePhone();
      this.activetab = 0;
    },
    computed: {
      ...mapGetters('data', ['countries', 'states', 'currencies', 'types', 'loadedCurrencies']),
      ...mapGetters('user', ['user', 'pending', 'validationError', 'hotel', 'lang']),
      ...mapState('user', ['hotelsPending']),
      ...mapState('description', ['descriptions', 'pmsError']),
      allCurrencies() {
        return this.currencies.data.result.map(({ id, name, code }) => ({ id, text: `${name} (${code})`, code }));
      },
      allTypes() {
        if ((typeof (this.types.data) !== 'undefined')) {
          return this.types.data.result.map(({ id, name }) => ({
            id,
            name,
          }));
        }
        return [];
      },
      invalidGeoData() {
        const { latitude, longitude } = this.address;
        if (latitude == null || longitude == null) return true;
        return !latitude.length || !longitude.length
          || !(Math.abs(longitude) <= 180)
          || !(Math.abs(latitude) <= 90);
      },
      locationUrl() {
        if (this.invalidGeoData) return '';
        // return `https://www.google.com/maps/@${this.address.latitude},${this.address.longitude},15z`;
        return `https://www.google.com/maps/place/${this.address.latitude},${this.address.longitude}`;
      },
      activeForm() {
        return this.$refs[`form${this.activetab}`];
      },
      formInvalid() {
        const form = this.activeForm;
        return form != null ? form.flags.invalid : true;
      },
      telRules() {
        return {
          required: true,
          max: 20,
        };
      },
    },
    methods: {
      ...mapActions('data', ['fetchCountries', 'fetchStates', 'fetchCurrencies', 'fetchPropertyTypes']),
      ...mapActions('user', ['getUser', 'getHotel', 'updateHotel', 'verifyPhone', 'getRoomDBPropertyId', 'getIdentifierSources', 'getPropertyIdentifiers', 'updatePropertyIdentifiers']),
      ...mapActions('description', ['getDescription']),
      setTab(tab) {
        switch (this.activetab) {
          case 0:
            this.resetPropertyForm();
            break;
          case 1:
            this.resetAddressForm();
            break;
          default:
            break;
        }
        this.activetab = tab;
      },
      async onSubmit() {
        if (this.activeForm.flags.invalid) return;
        try {
          const { code: lang } = this.lang;
          if (this.activetab === 0) {
            const { upload, remove } = this.logo;
            await this.updateHotel({
              partial: true,
              ...this.property,
              descriptions: this.descriptions,
              logo: { upload, remove },
              lang,
            });
          }
          if (this.activetab === 1) {
            await this.updateHotel({
              partial: true,
              descriptions: this.descriptions,
              ...this.address,
              lang,
            });
          } else if (this.activetab === 2) {
            await this.updatePropertyIdentifiers(this.identifiers);
          }
          this.activeForm.reset();
          this.$toastr.s({
            title: this.$t('pages.masterdata.alert-saved'),
            msg: '',
            timeout: 3000,
            progressbar: false,
          });
        } catch (error) {
          this.$toastr.e(error.message, this.$t('error'));
        }
        // this.$refs.title.scrollIntoView();
      },
      resetPropertyForm(resetForm = true) {
        const {
          // eslint-disable-next-line camelcase
          email, name, type, tel, alternative_email, website, capacity, currency_code,
          capacity_mode: cmode, hasMapped,
        } = JSON.parse(JSON.stringify(this.hotelData));
        // eslint-disable-next-line camelcase
        const capacity_mode = cmode != null ? cmode : 0;
        this.property = {
          email,
          name,
          type,
          tel,
          alternative_email,
          website,
          capacity,
          capacity_mode,
          currency_code,
          hasMapped,
        };
        this.logo = {
          original: this.hotel.logo || null,
          upload: null,
          remove: false,
        };
        if (resetForm) {
          this.$nextTick(() => {
            this.$refs.form0.reset();
          });
        }
      },
      resetAddressForm(resetForm = true) {
        const {
          // eslint-disable-next-line camelcase
          street, street_optional, zip, city, federal_state, state, country, latitude, longitude,
        } = JSON.parse(JSON.stringify(this.hotelData));
        this.address = {
          street, street_optional, zip, city, federal_state, state, country, latitude, longitude,
        };
        if (resetForm) {
          this.$nextTick(() => {
            this.$refs.form1.reset();
          });
        }
      },
      updateUserData() {
        this.resetPropertyForm();
        this.resetAddressForm();
      },
      toggleCurrencyModal(id) {
        this.currencyModal = id;
      },
      initProperty() {
        // this.property = {};
        // this.address = {};
        // this.profile = {};
      },
    },
  };
</script>
