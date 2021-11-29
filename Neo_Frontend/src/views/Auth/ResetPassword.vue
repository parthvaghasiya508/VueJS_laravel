<template>
  <div class="panel panel-form">
    <div class="panel-body">
      <img class="panel-image" src="/assets/images/cloud.svg" alt="Cloud image">
      <h1>{{ $t('reset-pwd.heading') }}</h1>
      <p>{{ $t('reset-pwd.tip') }}</p>

      <ValidationObserver ref="form" slim v-slot="{ handleSubmit }">
        <b-form @submit.prevent.stop="handleSubmit(submitForm)" novalidate>
          <input type="hidden" :value="email"/>

          <ValidatedField :rules="rulesForPassword()" type="password" name="password" autofocus
                          :placeholder="passwordPlaceholder" v-model="password" autocomplete="new-password"
                          :disabled="authenticating || resetTokenError!=null" :error-bag="validationError"/>

          <ValidatedField :rules="rulesForPassword(true)" type="password" name="password_confirmation"
                          v-model="password_confirmation" autocomplete="new-password"
                          :placeholder="$t('reset-pwd.password-confirm')"
                          :disabled="authenticating || resetTokenError!=null" :error-bag="validationError"/>

          <div class="alert alert-danger alert-btn-replacement" role="alert" v-if="resetTokenError">
            {{ resetTokenError }}
            <router-link :to="{ name: 'password.email' }">{{ $t('reset-pwd.button-try-again') }}</router-link>.
          </div>
          <submit-button v-else :loading="authenticating">
            {{ $t('reset-pwd.button-reset') }}
          </submit-button>
          <div class="alert alert-danger" role="alert" v-if="rateLimitError">
            {{ rateLimitError.message }}
          </div>
        </b-form>
      </ValidationObserver>
    </div>
    <div class="panel-footer mt-lg">
      <i18n tag="p" class="m-0" path="signup.signup">
        <template #signup>
          <router-link :to="{ name:'register' }"
                       :disabled="authenticating">{{ $t('signup.button-signup') }}</router-link>
        </template>
      </i18n>
    </div>
  </div>
</template>

<script>
  import { mapGetters, mapActions } from 'vuex';
  import PasswordHelper from '@/helpers/password.helper';

  export default {
    name: 'ResetPassword',
    data() {
      return {
        password: '',
        password_confirmation: '',

        token: null,
        email: null,
        passwordPlaceholder: PasswordHelper.generateRandomPwd(),
      };
    },
    created() {
      const { query } = this.$route;
      if (query != null) {
        const { token, email } = query;
        if (token && email) {
          this.token = token;
          this.email = email;
          return;
        }
      }
      this.$router.replace({ name: 'login' });
    },
    computed: {
      ...mapGetters('auth', ['authenticating', 'validationError', 'rateLimitError']),

      resetTokenError() {
        return this.validationError != null ? this.validationError.firstErrorFor('email') : null;
      },
    },
    methods: {
      ...mapActions('auth', ['resetPassword']),
      rulesForPassword(confirm = false) {
        const rules = {
          required_string: true,
          password: true,
        };
        if (confirm) {
          rules.sameAs = '@password';
        }
        return rules;
      },
      submitForm() {
        this.$nextTick(() => this.$refs.form.reset());
        const { email, token, password } = this;
        this.resetPassword({
          email,
          token,
          password,
        });
      },
    },
  };
</script>
