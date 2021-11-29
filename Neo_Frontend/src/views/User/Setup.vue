<template>
  <div class="page-setup" :class="pageClass">
    <div class="panel-title position-relative w-100 title d-md-none">
      <p>{{ $t('setup.page-title') }}</p>
    </div>
    <ValidationObserver ref="setupForm" tag="div" class="w-100 d-flex d-md-block flex-grow-1 flex-md-grow-0">
      <div v-if="setupStep===0" class="welcome">
        <div>
          <h3>
            <span><icon width="18" height="18" type="bubble-square-left"/></span>{{
              $t('setup.steps.0.greeting', { name: userName })
            }}
          </h3>
          <h1>{{ $t('setup.steps.0.heading') }}</h1>
          <p>
            {{ $t('setup.steps.0.tip') }}
            <!-- You may skip it though. -->
          </p>
          <div class="buttons">
            <!-- <b-btn variant="light" size="lg" :disabled="pending" @click="skipSetup">Skip</b-btn> -->
            <b-btn variant="secondary" size="lg" :disabled="pending" @click="nextStep">
              {{ $t('buttons.get-started') }}
            </b-btn>
          </div>
        </div>
        <div><img src="/assets/images/setup.svg" alt=""></div>
      </div>
      <div v-else class="steps">
        <div class="steps-progress" :class="pageClass">
          <div class="progress-step" v-for="({ text, active, done }, i) in steps" :key="`sp-${i}`">
            <div :class="{ active, done }"><icon class="done-mark" type="done-mark" width="19" height="12"/></div>
            <p>{{ text }}</p>
          </div>
        </div>
        <div class="current-step" v-html="currentStep"></div>
        <div v-if="setupStep===1">
          <div class="panel-title title setup-title">
            <p>{{ $t('setup.steps.1.greeting', { hotel: hotelName }) }}</p>
            <p class="hint">{{ $t('setup.steps.1.tip') }}</p>
          </div>
          <div class="sm-separator"></div>
          <div class="edge-block">
            <div class="left-edge"></div>
            <h3>{{ $t('setup.steps.1.section-hotel') }}</h3>
            <div class="row">
              <div class="col-12 cell-hotel-name">
                <ValidatedField name="name" v-model.trim="step1.name"
                                :placeholder="$t('addr.property-or-hotel-name')" rules="required|max:255"
                                :error-bag="validationError" :disabled="pending" class="mb-0"/>
              </div>
            </div>
          </div>
          <div class="sm-separator"></div>
          <div class="edge-block">
            <div class="left-edge"></div>
            <h3>{{ $t('setup.steps.1.section-contacts') }}</h3>
            <div class="row">
              <div class="col-12 cell-primary-email">
                <label for="email">{{ $t('pages.masterdata.primary-email') }}</label>
                <ValidatedField name="email" v-model.trim="step1.email"
                                :placeholder="$t('placeholder.email')" autocomplete="email"
                                rules="required|email" :error-bag="validationError" :disabled="pending" class="mb-0"/>
              </div>
              <div class="col-12 cell-tel">
                <label for="tel">{{ $t('pages.masterdata.property-tel') }}</label>
                <ValidatedField name="tel" v-model.trim="step1.tel" type="tel" autocomplete="tel"
                                rules="required|max:20" :error-bag="validationError" :disabled="pending"/>
              </div>
            </div>
            <div class="row">
              <div class="col-12">
                <p class="tip w-100">
                  {{ $t('pages.masterdata.primary-email-tip') }}
                </p>
              </div>
            </div>
          </div>
          <div class="sm-separator"></div>
          <div class="edge-block">
            <div class="left-edge"></div>
            <h3>{{ $t('setup.steps.1.section-currency') }}</h3>
            <p class="help">{{ $t('setup.steps.1.currency-tip') }}</p>
            <div class="row" v-if="loadedCurrencies">
              <div class="col-12 cell-currency">
                <label for="currency">{{ $t('pages.masterdata.choose-currency') }}</label>
                <drop-down id="currency" v-model="step1.currency_code" :items="allCurrencies"
                           grow="up" :disabled="pending"
                />
              </div>
            </div>
          </div>
        </div>
        <div v-else-if="setupStep===2">
          <div class="panel-title setup-title title">
            <p class="text-center text-md-left">{{ $t('pages.roomtypes.title') }}</p>
            <p class="hint">{{ $t('setup.steps.2.tip') }}</p>
          </div>
          <div class="sm-separator"></div>
          <room-types :payload="{ setup: true }" />
        </div>
        <div v-else-if="setupStep===3">
          <photos title-class="setup-title for-photos" />
        </div>
        <div v-else-if="setupStep===4">
          <div class="panel-title setup-title title">
            <p class="text-center text-md-left">{{ $t('pages.rateplans.title') }}</p>
            <p class="hint">{{ $t('setup.steps.4.tip') }}</p>
          </div>
          <div class="sm-separator"></div>
          <rate-plans/>
        </div>
        <div v-else-if="setupStep===5">
          <div class="panel-title title setup-title">
            <p>{{ $t('setup.steps.5.heading') }}</p>
            <p class="hint">{{ $t('setup.steps.5.tip') }}</p>
          </div>
          <div class="sm-separator"></div>
          <div class="edge-block">
            <div class="left-edge"></div>
            <div class="row">
              <div class="col-12 cell-url">
                <label>{{ $t('setup.steps.5.engine-url') }}</label>
                <div class="copyable">
                  <b-form-group class="mb-3">
                    <b-input readonly :value="urlLink" class="mb-0" ref="extLink" />
                    <b-btn variant="secondary" @click="copyUrl">{{ $t('buttons.copy') }}</b-btn>
                    <b-form-valid-feedback tooltip ref="extLinkTip">
                      {{ $t('setup.steps.5.msg-copied') }}
                    </b-form-valid-feedback>
                  </b-form-group>
                </div>
                <p class="url"><a :href="urlLink" target="_blank" rel="noopener">
                  {{ $t('setup.steps.5.link-visit-engine') }}
                  <icon type="new-window" width="14" height="14"/>
                </a></p>
                <hr>
                <label>{{ $t('setup.steps.5.learn-more') }}</label>
                <p class="url"><a @click.stop.prevent href="#">
                  {{ $t('setup.steps.5.links')[0] }}
                </a></p>
                <p class="url"><a @click.stop.prevent href="#">
                  {{ $t('setup.steps.5.links')[1] }}
                </a></p>
                <p class="url"><a @click.stop.prevent href="#">
                  {{ $t('setup.steps.5.links')[2] }}
                </a></p>
              </div>
            </div>
          </div>
        </div>
        <div class="buttons">
          <b-btn v-if="showBackButton" variant="outline-primary" @click="prevStep" :disabled="pending">
            {{ $t('buttons.back') }}
          </b-btn>
          <submit-button v-if="setupStep < 5" variant="secondary" @click="nextStep" inline
                         :loading="pending" :disabled="formInvalid">{{ $t('buttons.continue') }}</submit-button>
          <submit-button v-else inline type="button" @click="nextStep" :loading="pending">
            {{ $t('buttons.finish') }}
          </submit-button>
        </div>
      </div>
    </ValidationObserver>
  </div>
</template>

<script>
  import { mapGetters, mapActions } from 'vuex';
  import RoomTypes from '@/components/Pages/RoomTypes.vue';
  import Photos from '@/components/Pages/Photos.vue';
  import RatePlans from '@/components/Pages/RatePlans.vue';

  const allSteps = [
    'setup.steps.1.title',
    'pages.roomtypes.title',
    'pages.photos.title',
    'pages.rateplans.title',
    'setup.steps.5.title',
  ];

  export default {
    name: 'Setup',
    components: {
      RoomTypes, Photos, RatePlans,
    },
    data() {
      return {
        step1: {
          name: '',
          currency_code: '',
          email: '',
          tel: '',
        },
        hotelData: null,
        formExists: false,
      };
    },
    computed: {
      ...mapGetters('data', ['countries', 'currencies', 'loadedCurrencies']),
      ...mapGetters('user', [
        'user', 'hotelName', 'hotelID', 'pending', 'validationError', 'setupStep', 'hotel', 'engineURL', 'isAgentUser',
      ]),
      ...mapGetters('roomtypes', ['noRooms']),
      ...mapGetters('rateplans', ['noPlans']),

      allCurrencies() {
        return this.currencies.data.result.map(({ name, code }) => ({ id: code, text: `${name} (${code})` }));
      },
      pageClass() {
        return `step-${this.setupStep} ${this.isAgentUser ? 'setup-agent' : ''}`;
      },
      userName() {
        return this.user && this.user.profile ? this.user.profile.name : '';
      },
      urlLink() {
        return `${this.engineURL}&hotelcode=${this.hotelID}`;
      },
      showBackButton() {
        return this.setupStep < 5 && (!this.isAgentUser || this.setupStep > 2);
      },
      steps() {
        const step = this.setupStep - 1;
        return allSteps.map((text, i) => ({
          text: this.$t(text),
          active: step === i,
          done: step > i,
        })).filter((_, i) => !this.isAgentUser || i !== 0);
      },
      currentStep() {
        const { steps } = this;
        const total = steps.length;
        const current = steps.find((s) => s.active);
        if (!current) return '';
        return `${current.text} <span>(${this.setupStep}/${total})</span>`;
      },
      formInvalid() {
        switch (this.setupStep) {
          case 1:
            return !this.formExists || !this.$refs.setupForm.flags.valid;
          case 2:
            return this.noRooms;
          case 4:
            return this.noPlans;
          default:
            return false;
        }
      },
    },
    async created() {
      //
    },
    async mounted() {
      await Promise.allSettled([
        this.fetchCurrencies(),
        this.getHotel().then((data) => {
          this.hotelData = data;
          return true;
        }),
      ]);
      this.updateState();
    },
    watch: {
      async setupStep(n, o) {
        if (n === 1 && o > 1) {
          this.hotelData = await this.getHotel();
        }
        this.updateState();
      },
    },
    methods: {
      ...mapActions('data', ['fetchCurrencies']),
      ...mapActions('user', ['updateSetup', 'getHotel']),

      updateState() {
        const title = this.$tc('setup.title', this.setupStep);
        this.$store.commit('pageTitle', title);
        this.$store.commit('stretch', !this.setupStep);
        if (this.setupStep === 1) {
          const {
            // eslint-disable-next-line camelcase
            name, currency_code, email, tel,
          } = this.hotelData;
          this.step1 = {
            name, currency_code, email, tel,
          };
          this.formExists = false;
          this.$nextTick(() => {
            this.$refs.setupForm.reset();
            this.formExists = true;
          });
        }
      },
      copyUrl() {
        this.$refs.extLink.select();
        document.execCommand('copy');
        navigator.clipboard.writeText(this.urlLink);
        const tip = this.$refs.extLinkTip;
        tip.classList.add('visible');
        setTimeout(() => tip.classList.remove('visible'), 1000);
      },
      skipSetup() {
        this.updateSetup({ step: -1 });
      },
      nextStep() {
        const payload = this.setupStep === 1 ? { ...this.step1 } : {};
        payload.step = this.setupStep + 1;
        this.updateSetup(payload);
      },
      prevStep() {
        this.updateSetup({ step: this.setupStep - 1 });
      },
    },
  };
</script>
