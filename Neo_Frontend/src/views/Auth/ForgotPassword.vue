<template>
  <fragment>
    <div class="panel panel-status" v-if="resetPasswordSent">
      <div class="panel-body">
        <img class="panel-image" src="/assets/images/envelop.svg" alt="Envelop image"/>
        <h1>{{ $t('forgot-pwd.sent-heading') }}</h1>
        <p>{{ $t('forgot-pwd.sent-to') }}</p>
        <h3 class="mb-0">{{ email }}</h3>
      </div>
      <div class="panel-footer">
        <p class="m-0">{{ $t('forgot-pwd.sent-tip') }}</p>
      </div>
    </div>
    <div class="panel panel-form" v-else>
      <div class="panel-body">
        <img class="panel-image" src="/assets/images/cloud.svg" alt="Cloud image">
        <h1>{{ $t('forgot-pwd.heading') }}</h1>
        <p>{{ $t('forgot-pwd.tip') }}</p>
        <ValidationObserver ref="form" slim v-slot="{ handleSubmit }">
          <b-form @submit.prevent.stop="handleSubmit(submitForm)" novalidate>

            <ValidatedField rules="required|email" type="email" name="email" :placeholder="$t('placeholder.email')"
                            v-model.trim="email" autocomplete="email" autofocus
                            :disabled="authenticating" :error-bag="validationError"/>

            <submit-button :loading="authenticating">
              {{ $t('forgot-pwd.button-send') }}
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
  </fragment>
</template>

<script>
  import { mapGetters, mapActions } from 'vuex';

  export default {
    name: 'ForgotPassword',
    data() {
      return {
        email: '',
      };
    },
    computed: {
      ...mapGetters('auth', ['authenticating', 'validationError', 'resetPasswordSent', 'rateLimitError']),
    },
    methods: {
      ...mapActions('auth', ['sendResetPasswordEmail']),

      submitForm() {
        this.$nextTick(() => this.$refs.form.reset());
        const { email } = this;
        this.sendResetPasswordEmail({ email });
      },
    },
  };
</script>
