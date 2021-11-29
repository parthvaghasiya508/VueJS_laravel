<template>
  <div class="panel panel-form">
    <div class="panel-body">
      <img class="panel-image" src="/assets/images/cloud.svg" alt="Cloud image">
      <h1>{{ $t('login.heading') }}</h1>
      <p v-if="!isAgentLogin && !loginDisabled">{{ $t('login.tip') }}</p>
      <div class="alert alert-warning" v-if="loginDisabled">
        {{ $t('login.agents-login-disabled') }}
      </div>
      <ValidationObserver ref="form" slim v-slot="{ handleSubmit }" v-if="!isAgentLogin && !loginDisabled">
        <b-form @submit.prevent.stop="handleSubmit(submitForm)" novalidate>
          <b-form-group class="text-left mb-0">
            <label for="email" class="text-sm">{{ $t('login.emailLabel') }}</label>
            <ValidatedField rules="required|email" :placeholder="$t('login.emailPlaceholder')" type="email" name="email"
                            v-model.trim="email" autocomplete="email" autofocus
                            :disabled="loginDisabled || authenticating" :error-bag="validationError"/>
          </b-form-group>
          <b-form-group class="text-left mb-0">
            <label for="password" class="text-sm">{{ $t('login.pwdLabel') }}</label>
            <ValidatedField rules="required" :placeholder="$t('login.pwdPlaceholder')" name="password"
                            type="password" v-model="password" autocomplete="current-password" local
                            :disabled="loginDisabled || authenticating" :error-bag="validationError"/>
          </b-form-group>

          <b-form-invalid-feedback class="mt-0 mb-1" :class="{'d-block':authError}">
            {{ authError }}
          </b-form-invalid-feedback>
          <submit-button :loading="authenticating" :disabled="loginDisabled">
            {{ $t('login.btnLogin') }}
          </submit-button>
          <div class="alert alert-danger" role="alert" v-if="rateLimitError">
            {{ rateLimitError.message }}
          </div>
        </b-form>
      </ValidationObserver>
      <div class="d-flex justify-content-between" v-if="!loginDisabled || !resetDisabled">
        <check-box v-model="remember" v-if="!loginDisabled">
          {{ $t('login.rememberMe') }}
        </check-box>
        <router-link
          :to="{ name:'password.email' }"
          :disabled="authenticating"
          v-if="!resetDisabled"
        >
          {{ $t('login.linkResetPassword') }}
        </router-link>
      </div>
      <div v-if="isAgentLogin" class="d-flex align-items-center justify-content-center">
        <h5 v-if="validationError" class="text-danger mb-0">
          {{ validationError.firstErrorFor('uuid') }}
        </h5>
        <h5 v-else-if="rateLimitError" class="text-danger mb-0">
          {{ rateLimitError.message }}
        </h5>
        <template v-else>
          <h5 class="flex-grow-0 mb-0 mr-2">{{ $t('login.agents-login') }}</h5>
          <spinner class="d-inline-block flex-grow-0 w-auto" />
        </template>
      </div>
    </div>
    <div class="panel-footer" v-if="!isAgentLogin && !registerDisabled">
      <i18n tag="p" class="m-0" path="login.signup">
        <template #signup>
          <router-link :to="{ name:'register' }"
                       :disabled="authenticating">{{ $t('login.btnSignup') }}</router-link>
        </template>
      </i18n>
    </div>
  </div>
</template>

<script>
  import { mapGetters, mapActions } from 'vuex';

  export default {
    name: 'Login',
    data() {
      return {
        email: '',
        password: '',
        remember: false,
      };
    },
    mounted() {
      if (this.isAgentLogin != null) {
        this.$nextTick(this.submitUuid);
      }
    },
    computed: {
      ...mapGetters('auth', ['authenticating', 'authError', 'validationError', 'rateLimitError']),
      ...mapGetters('user', ['isAgentDomain']),
      isAgentLogin() {
        const { uuid } = this.$route.params;
        return uuid || null;
      },
      loginDisabled() {
        return this.isAgentDomain;
      },
      registerDisabled() {
        return this.isAgentDomain;
      },
      resetDisabled() {
        return this.isAgentDomain;
      },
    },
    methods: {
      ...mapActions('auth', ['login']),

      submitForm() {
        const { email, password, remember } = this;
        this.$nextTick(() => this.$refs.form.reset());
        this.login({
          email,
          password,
          remember,
        });
      },

      submitUuid() {
        const uuid = this.isAgentLogin;
        this.login({ uuid });
      },
    },
  };
</script>
