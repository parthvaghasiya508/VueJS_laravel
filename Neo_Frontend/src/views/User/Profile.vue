<template>
  <div class="page-profile" id="page-profile">
    <div ref="title" class="panel-title position-relative w-100 title">
      <p>{{ $t('pages.profile.title') }}</p>
    </div>
    <Tabs
      v-model="activeTab"
      :items="tabs"
      withContent
    >
      <template #tab(personal-data)>
        <div class="list">
          <ValidationObserver ref="form-personal-data">
            <form @submit.prevent="onSubmit">
              <!--<div class="row">-->
              <!--<div class="col-4">-->
              <!--<PictureSelector v-model="profileImage"/>-->
              <!--</div>-->
              <!--</div>-->
              <div class="row">
                <div class="col-xl-4 col-lg-6">
                  <p class="head-line first-name">
                    {{ $t('pages.profile.first-name') }}
                  </p>
                  <ValidatedField v-model="profile.first_name"
                                  :placeholder="$t('pages.profile.first-name')"
                                  rules="required_string|max:100"
                                  type="text"
                                  name="first-name"/>
                  <p class="head-line last-name">
                    {{ $t('pages.profile.last-name') }}
                  </p>
                  <ValidatedField v-model="profile.last_name"
                                  group-class="mb-0"
                                  :placeholder="$t('pages.profile.last-name')"
                                  rules="required_string|max:100"
                                  type="text"
                                  name="last-name"/>
                </div>
                <div class="col-xl-4 col-lg-6 mt-3 mt-lg-0">
                  <p class="head-line phone-number">
                    {{ $t('pages.profile.phone-number') }}
                  </p>
                  <ValidatedField v-model.trim="profile.tel"
                                  group-class="mb-0"
                                  name="tel"
                                  type="tel"
                                  national-mode
                                  autocomplete="tel"
                                  rules="required|max:20"/>
                </div>
              </div>
            </form>
          </ValidationObserver>
        </div>
      </template>
      <template #tab(login-data)>
        <div class="list">
          <div class="row">
            <div class="change-email-section w-100 mb-4">
              <p class="col-12 head-line email">
                {{ $t('pages.profile.email') }}
              </p>
              <p class="col-12 head-line">
                {{ $t('pages.profile.email-change-text') }}
              </p>
              <div class="col-xl-3 col-lg-4 col-md-6 col-sm">
                <ValidationObserver ref="form" slim v-slot="{ handleSubmit }">
                  <b-form @submit.prevent.stop="handleSubmit(submitChangeEmail)" novalidate>
                    <b-button block variant="outline-primary"
                              type="submit"
                              :disabled="authenticating">
                      {{ $t('buttons.change-email') }}
                    </b-button>
                  </b-form>
                </ValidationObserver>
              </div>
            </div>
          </div>
          <div class="row">
            <p class="col-12 head-line password">
              {{ $t('pages.profile.password') }}
            </p>
            <p class="col-12 head-line">
              {{ $t('pages.profile.password-change-text') }}
            </p>
            <div class="col-xl-3 col-lg-4 col-md-6 col-sm">
              <ValidationObserver ref="form" slim v-slot="{ handleSubmit }">
                <b-form @submit.prevent.stop="handleSubmit(submitChangePassword)" novalidate>
                  <b-button block variant="outline-primary"
                            type="submit"
                            :disabled="authenticating">
                    {{ $t('buttons.change-password') }}
                  </b-button>
                </b-form>
              </ValidationObserver>
            </div>
            <div class="col-12">
              <div class="alert alert-danger" role="alert" v-if="rateLimitError">
                {{ rateLimitError.message }}
              </div>
            </div>
          </div>
        </div>
      </template>
      <template #tab(formats-language-data)>
        <div class="list">
          <ValidationObserver ref="form-formats-language-data">
            <form @submit.prevent="onSubmit">
              <div class="row">
                <div class="col-xl-4 col-lg-6">
                  <p class="head-line date-format">
                    {{ $t('pages.profile.date-format') }}
                  </p>
                  <ValidatedField v-model="profile.date_format"
                                  :placeholder="$t('pages.profile.date-format-placeholder')"
                                  rules="required_string|date_format|max:11"
                                  type="text"
                                  name="date-format"/>
                  <br>

                  <p class="head-line number-format">
                    {{ $t('pages.profile.number-format') }}
                  </p>
                  <drop-down
                    id="number-format"
                    id-key="code"
                    label-key="format"
                    :items="formatedNumberFormats"
                    :disabled="pending"
                    v-model="profile.number_format"
                  />
                  <br>

                  <p class="head-line default-language">
                    {{ $t('pages.profile.default-language') }}
                  </p>
                  <drop-down
                    id="default-language"
                    id-key="code"
                    label-key="name"
                    :items="langs"
                    :disabled="pending"
                    v-model="profile.default_language"
                  />
                </div>
              </div>
            </form>
          </ValidationObserver>
        </div>
      </template>
    </Tabs>
    <div
      v-if="['personal-data', 'formats-language-data'].includes(activeTab)"
      class="panel-footer padding-left-md padding-right-md">
      <div class="row">
        <div class="col-lg-2 col-md-4 col-sm">
          <b-button block variant="outline-primary" @click="resetProfileForm">
            {{ $t('buttons.cancel') }}
          </b-button>
        </div>
        <div class="col-lg-2 col-md-4 col-sm">
          <b-button block variant="primary"
                    type="button"
                    @click="onSubmit">
            {{ $t('buttons.save') }}
          </b-button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
  import { mapActions, mapGetters } from 'vuex';
  import { fakeNumber, numberFormats } from '@/shared';

  export default {
    name: 'Profile',
    data() {
      return {
        activeTab: 'personal-data',
        tabs: [
          { id: 'personal-data', title: this.$t('pages.profile.tabs.personal-data') },
          { id: 'login-data', title: this.$t('pages.profile.tabs.login-data') },
          { id: 'formats-language-data', title: this.$t('pages.profile.tabs.formats-language-data') },
        ],
        profile: {},
        profileImage: null,
        userData: null,
      };
    },
    async created() {
      this.userData = this.user.profile;
      // await this.getUser();
      // await Promise.allSettled([
      //   //   this.fetchCountries(),
      //   //   this.fetchCurrencies(),
      //   this.getUser().then((data) => {
      //     console.log(this.getUser());
      //     this.userData = data;
      //     return true;
      //   }),
      // ]);
      this.updateProfile();
    },
    computed: {
      ...mapGetters('user', ['user', 'pending', 'validationError']),
      ...mapGetters('auth', ['authenticating', 'validationError', 'resetPasswordSent', 'changeEmailSent', 'rateLimitError']),
      ...mapGetters('data', ['languages']),
      activeForm() {
        return this.$refs[`form-${this.activeTab}`];
      },
      dateFormats() {
        return this.$t('pages.profile.date-formats');
      },
      formatedNumberFormats() {
        return numberFormats.map(
          (format) => ({ code: format.code, format: new Intl.NumberFormat(format.code, { style: 'currency', currency: format.currency }).format(fakeNumber) }),
        );
      },
      langs() {
        return this.languages.map(({ nativeName, code }) => ({ code, name: `${nativeName}` }));
      },
    },
    watch: {
      resetPasswordSent(sent) {
        if (sent) {
          this.logout({
            forced: false,
            stay: false,
            moveTo: {
              name: 'password.change', query: { email: this.user.email },
            },
          });
        }
      },
      changeEmailSent(sent) {
        if (sent) {
          this.logout({
            forced: false,
            stay: false,
            moveTo: {
              name: 'email.change', query: { email: this.user.email },
            },
          });
        }
      },
    },
    methods: {
      ...mapActions('user', ['getUser', 'updateProfileData']),
      ...mapActions('auth', ['sendResetPasswordEmail', 'sendChangeEmail', 'logout']),
      ...mapActions('data', ['fetchLanguages']),
      async onSubmit() {
        if (this.activeForm.flags.invalid) return;
        let data = {};
        const {
          // eslint-disable-next-line camelcase
          first_name, last_name, tel, date_format, number_format, default_language,
        } = this.profile;

        const tabsData = {
          'personal-data': { first_name, last_name, tel },
          'formats-language-data': { date_format, number_format, default_language },
        };

        if (Object.keys(tabsData).includes(this.activeTab)) data = tabsData[this.activeTab];
        else return;

        try {
          // const { upload, remove } = this.logo;
          await this.updateProfileData({
            partial: true,
            ...data,
            // logo: { upload, remove },
          });
          this.activeForm.reset();
          this.$toastr.s({
            title: this.$t('pages.profile.alert-saved'),
            msg: '',
            timeout: 3000,
            progressbar: false,
          });
        } catch (error) {
          this.$toastr.e(error.message, this.$t('error'));
        }
      },
      resetProfileForm(resetForm = true) {
        if (this.activeTab === 'personal-data') {
          const {
            // eslint-disable-next-line camelcase
            first_name, last_name, tel, date_format, number_format, default_language,
          } = JSON.parse(JSON.stringify(this.userData));
          this.profile = {
            first_name, last_name, tel, date_format, number_format, default_language,
          };
          // this.logo = {
          //   original: this.hotel.logo || null,
          //   upload: null,
          //   remove: false,
          // };
          if (resetForm) {
            this.$nextTick(() => {
              this.$refs[`form-${this.activeTab}`].reset();
            });
          }
        }
      },
      updateProfile() {
        this.resetProfileForm();
      },
      submitChangeEmail() {
        this.sendChangeEmail();
      },
      submitChangePassword() {
        this.sendResetPasswordEmail({ email: this.user.email });
      },
    },
  };
</script>
