<template>
  <div class="panel panel-form">
    <div class="panel-body" ignore-enforce-focus-selector=".ss-search > input">
      <img class="panel-image" src="/assets/images/cloud.svg" alt="Envelop image"/>
      <h1>{{ $t(`details.steps.${+pdFilled}.title`) }}</h1>
      <p v-if="!pdFilled">{{ $t(`details.steps.${+pdFilled}.tip`) }}</p>

      <ValidationObserver ref="form" slim v-slot="{ handleSubmit }">
        <b-form @submit.prevent.stop="handleSubmit(submitForm)" novalidate>
          <template v-if="!pdFilled">
            <b-form-group class="text-left mb-0">
              <label for="first_name" class="text-sm mb-1">{{ $t('addr.first-name') }}</label>
              <ValidatedField name="first_name" v-model.trim="first_name" class="mb-3" autocomplete="given-name"
                              autofocus rules="required|max:255" :error-bag="validationError" :disabled="pending"/>
            </b-form-group>
            <b-form-group class="text-left mb-0">
              <label for="last_name" class="text-sm mb-1">{{ $t('addr.last-name') }}</label>
              <ValidatedField name="last_name" v-model.trim="last_name" class="mb-3" autocomplete="family-name"
                              rules="required|max:255" :error-bag="validationError" :disabled="pending"/>
            </b-form-group>
            <b-form-group class="text-left mb-0">
              <label for="tel" class="text-sm mb-1">{{ $t('addr.phone') }}</label>
              <ValidatedField name="tel" v-model.trim="tel" type="tel" class="mb-3" autocomplete="tel"
                              rules="required|max:20" :error-bag="validationError" :disabled="pending"/>
            </b-form-group>
            <!--
            <ValidatedField name="tel" v-model.trim="tel" type="tel"
                            autocomplete="tel"
                            rules="required|max:20" :error-bag="validationError" :disabled="pending"/>
            -->
          </template>

          <template v-else>

            <b-form-group>
              <radio v-model="skipHotel" :val="true" :disabled="pending" class="mb-3 text-left">
                {{ $t('details.property-register-later') }}
              </radio>
              <radio v-model="skipHotel" :val="false" :disabled="pending" class="mb-1 text-left">
                {{ $t('details.property-register-now') }}
              </radio>
            </b-form-group>

            <template v-if="skipHotel===false">
              <b-form-group class="text-left mb-0">
                <label for="name" class="text-sm mb-1">{{ $t('addr.property-or-hotel-name') }}</label>
                <ValidatedField name="name" v-model.trim="name" rules="required|max:255" class="mb-3"
                                :error-bag="validationError" :disabled="pending" autofocus/>
              </b-form-group>
              <b-form-group class="text-left mb-0">
                <label for="street" class="text-sm mb-1">{{ $t('addr.street') }}</label>
                <ValidatedField name="street" v-model.trim="street" autocomplete="street-address" class="mb-3"
                                rules="required|max:255" :error-bag="validationError" :disabled="pending"/>
              </b-form-group>

              <b-form-row>
                <b-form-group class="text-left mb-0 col-md-6">
                  <label for="htel" class="text-sm mb-1">{{ $t('addr.property-phone') }}</label>
                  <ValidatedField name="htel" v-model.trim="htel" type="tel" autocomplete="tel"
                                  rules="required|max:20" :error-bag="validationError" :disabled="pending"/>
                </b-form-group>

                <b-form-group class="text-left mb-0 col-md-6">
                  <label for="zip" class="text-sm mb-1">{{ $t('addr.zip') }}</label>
                  <ValidatedField name="zip" v-model.trim="zip" type="text" class="mb-3"
                                  autocomplete="postal-code" rules="required_string|max:10"
                                  :error-bag="validationError" :disabled="pending"/>
                </b-form-group>
              </b-form-row>

              <b-form-row>
                <b-form-group class="text-left mb-0 col-md-6">
                  <label for="city" class="text-sm mb-1">{{ $t('addr.city') }}</label>
                  <ValidatedField name="city" v-model.trim="city" class="mb-3" autocomplete="address-level2"
                                  rules="required|max:255" :error-bag="validationError" :disabled="pending"/>
                </b-form-group>

                <b-form-group class="text-left mb-0 col-md-6">
                  <label for="country" class="text-sm mb-1">{{ $t('addr.country') }}</label>
                  <ValidatedField name="country" v-model="country" type="select" class="mb-3" rules="required"
                                  track-by="code" label-by="name" autocomplete="country-name" :options="countries"
                                  :error-bag="validationError" :disabled="pending || countries==null" searchable/>
                </b-form-group>
              </b-form-row>

              <b-form-row>
                <b-form-group class="text-left mb-0 col-md-6">
                  <label for="email" class="text-sm mb-1">{{ $t('addr.property-email') }}</label>
                  <ValidatedField name="email" v-model.trim="email" class="mb-3" autocomplete="email"
                                  rules="required|email" :error-bag="validationError" :disabled="pending"/>
                </b-form-group>

                <b-form-group class="text-left mb-0 col-md-6">
                  <label for="currency" class="text-sm mb-1">{{ $t('labels.currency') }}</label>
                  <drop-down
                    v-if="allCurrencies"
                    id="currency" :items="allCurrencies"
                    :disabled="pending || currencies==null" v-model="currency_code"/>
                </b-form-group>
              </b-form-row>
            </template>

          </template>

          <submit-button :loading="pending" :disabled="pdFilled && skipHotel==null">
            {{ $t(`details.steps.${+pdFilled}.button`) }}
          </submit-button>
          <div class="alert alert-danger" role="alert" v-if="hotelRegistrationError != null">
            {{ hotelRegistrationError }}
          </div>
          <p v-if="0&&pdFilled" class="m-0">
            <b-btn variant="link" class="m-0 p-0 h-auto"
                   @click.stop="skipHotelRegistration">{{ $t('details.steps.1.button-skip') }}</b-btn>
          </p>
        </b-form>
      </ValidationObserver>
    </div>
    <div class="panel-footer progress-menu" v-if="0">
      <div class="d-flex justify-content-between">
        <small>{{ $t('details.steps.0.title') }}</small>
        <small>{{ $t('details.steps.1.title') }}</small>
      </div>
      <div class="progress">
        <div class="progress-bar" role="progressbar" :style="{ width: `${progress}%` }"
             :aria-valuenow="progress" aria-valuemin="0" aria-valuemax="100"></div>
      </div>
    </div>
  </div>
</template>

<script>
  import { mapGetters, mapActions } from 'vuex';

  export default {
    name: 'Details',
    data() {
      return {
        first_name: '',
        last_name: '',
        tel: '',
        name: '',
        street: '',
        htel: '',
        zip: '',
        city: '',
        country: '',
        email: '',
        skipHotel: null,
        currency_code: 'EUR',
      };
    },
    computed: {
      ...mapGetters('data', ['countries', 'currencies', 'loadedCurrencies']),
      ...mapGetters('user', ['pdFilled', 'user', 'pending', 'validationError']),

      progress() {
        return this.pdFilled ? 100 : 50;
      },
      hotelRegistrationError() {
        return this.validationError ? this.validationError.firstErrorFor('hotel') : null;
      },
      allCurrencies() {
        if (this.loadedCurrencies) {
          return this.currencies.data.result.map(({ name, code }) => ({ id: code, text: `${name} (${code})` }));
        }
        return [];
      },
    },
    async created() {
      this.updateTitle();
      await Promise.allSettled([
        this.fetchCountries(),
        this.fetchCurrencies(),
      ]);
    },
    watch: {
      pdFilled() {
        this.updateTitle();
      },
      skipHotel() {
        this.$nextTick(this.resetForm);
      },
    },
    mounted() {
      this.$nextTick(this.resetForm);
    },
    methods: {
      ...mapActions('data', ['fetchCountries', 'fetchCurrencies']),
      ...mapActions('user', ['updateProfile']),

      updateTitle() {
        const title = this.$t(`details.steps.${+this.pdFilled}.title`).capitalizeAll();
        this.$store.commit('pageTitle', title);
      },

      resetForm() {
        this.$refs.form.reset();
      },

      skipHotelRegistration() {
        this.$nextTick(() => this.$refs.form.reset());
        this.updateProfile({ skip: true });
      },

      async submitForm() {
        this.$nextTick(() => this.$refs.form.reset());
        if (!this.pdFilled) {
          // eslint-disable-next-line camelcase
          const { first_name, last_name, tel } = this;
          await this.updateProfile({
            first_name,
            last_name,
            tel,
          });
        } else if (this.skipHotel) {
          this.skipHotelRegistration();
        } else {
          const {
            // eslint-disable-next-line camelcase
            country, city, zip, htel, street, name, email, currency_code,
          } = this;
          await this.updateProfile({
            country,
            city,
            zip,
            htel,
            street,
            name,
            email,
            currency_code,
          });
        }
        this.resetForm();
      },
    },
  };
</script>
