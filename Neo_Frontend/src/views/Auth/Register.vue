<template>
  <div class="panel panel-form">
    <div class="panel-body">
      <img class="panel-image" src="/assets/images/cloud.svg" alt="Cloud image">
      <h1>{{ $t('signup.title') }}</h1>
      <p>{{ $t(`signup.steps.${+step}.tip`) }}</p>
      <ValidationObserver ref="form" slim v-slot="{ handleSubmit }">
        <b-form @submit.prevent="handleSubmit(submitForm)" novalidate>
          <b-form-group class="text-left mb-0">
            <label for="email" class="text-sm">{{ $t('labels.email') }}</label>
            <ValidatedField rules="required|email" :placeholder="$t('placeholder.email')" type="email" name="email"
                            v-model.trim="email" autocomplete="email" autofocus
                            :disabled="authenticating" :error-bag="validationError"/>
          </b-form-group>
          <b-form-group class="text-left mb-0" v-if="step">
            <label for="password" class="text-sm">{{ $t('labels.password') }}</label>
            <ValidatedField rules="required_string|password" type="password" name="password"
                            autofocus autovisible autogenerator
                            v-model="password" autocomplete="new-password" :placeholder="$t('placeholder.password')"
                            :disabled="authenticating" :error-bag="validationError"/>
          </b-form-group>
          <submit-button :loading="authenticating">
            {{ $t(`signup.steps.${+step}.button`) }}
          </submit-button>
          <div class="alert alert-danger" role="alert" v-if="rateLimitError">
            {{ rateLimitError.message }}
          </div>

          <ValidatedField :rules="step?'accepted':''" type="checkbox" name="tos_agreed" class="tos-checkbox"
                          v-model="tos_agreed" :disabled="authenticating" :error-bag="validationError">
            <i18n :tag="false" path="signup.agreement">
              <template #tos>
                <a :href="`https://www.cultbooking.com/${lang.code}/terms-conditions/`"
                   target="_blank">{{ $t('signup.link-tos') }}</a>
              </template>
              <template #privacy>
                <a class="d-inline-block" :href="`https://www.cultbooking.com/${lang.code}/privacy-policy/`"
                   target="_blank">{{ $t('signup.link-privacy') }}</a>
              </template>
            </i18n>
          </ValidatedField>
        </b-form>
      </ValidationObserver>
    </div>
    <div class="panel-footer">
      <i18n tag="p" class="m-0" path="login.login">
        <template #login>
          <router-link :to="{ name: 'login' }"
                       :disabled="authenticating">{{ $t('login.button-login') }}</router-link>
        </template>
      </i18n>
    </div>
  </div>
</template>

<script>
  import { mapGetters, mapActions } from 'vuex';
  import { email as emailValidator } from 'vee-validate/dist/rules';
  import PasswordHelper from '@/helpers/password.helper';

  export default {
    name: 'Register',
    data() {
      return {
        step: false,
        email: '',
        password: PasswordHelper.generateRandomPwd(10),
        tos_agreed: false,
      };
    },
    computed: {
      ...mapGetters('auth', ['authenticating', 'authError', 'validationError', 'rateLimitError']),
      ...mapGetters('user', ['lang']),
    },
    mounted() {
      const { email } = this.$route.query;
      if (email == null) return;
      if (emailValidator.validate(email)) {
        this.email = email;
        this.step = true;
      }
      this.$router.replace({ name: 'register' });
    },
    methods: {
      ...mapActions('auth', ['register']),

      submitForm() {
        if (!this.step) {
          this.step = true;
          this.$nextTick(() => {
            this.$refs.form.reset();
          });
          // this.$nextTick(() => this.$refs.password.focus());
          return;
        }
        // eslint-disable-next-line camelcase
        const { email, password, tos_agreed } = this;
        this.$nextTick(() => this.$refs.form.reset());
        this.register({ email, password, tos_agreed });
      },
    },
  };
</script>
