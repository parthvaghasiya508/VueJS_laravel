<template>
  <div class="panel panel-form">
    <div class="panel-body">
      <img class="panel-image" src="/assets/images/cloud.svg" alt="Cloud image">
      <h1>{{ $t('change-email.heading') }}</h1>
      <p>{{ $t('change-email.tip') }}</p>

      <ValidationObserver ref="form" slim v-slot="{ handleSubmit }">
        <b-form @submit.prevent.stop="handleSubmit(submitForm)" novalidate>

          <ValidatedField rules="required_string|min:8" type="email" name="newEmail" autofocus
                          :placeholder="$t('change-email.email-new')" v-model="newEmail" autocomplete="new-email"
                          :disabled="authenticating || resetTokenError!=null" :error-bag="validationError"/>

          <ValidatedField rules="required_string|sameAs:@newEmail" type="email" name="newEmail_confirmation"
                          v-model="newEmail_confirmation" autocomplete="new-email"
                          :placeholder="$t('change-email.email-confirm')"
                          :disabled="authenticating || resetTokenError!=null" :error-bag="validationError"/>

          <div class="alert alert-danger alert-btn-replacement" role="alert" v-if="resetTokenError">
            {{ resetTokenError }}
            <router-link :to="{ name: 'password.email' }">{{ $t('reset-pwd.button-try-again') }}</router-link>.
          </div>
          <submit-button v-else :loading="authenticating">
            {{ $t('change-email.button-change') }}
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
  import { mapActions, mapGetters } from 'vuex';

  export default {
    name: 'ChangeEmail',
    data() {
      return {
        newEmail: '',
        newEmail_confirmation: '',
        token: null,
        email: null,
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
      ...mapActions('auth', ['changeEmail']),

      submitForm() {
        this.$nextTick(() => this.$refs.form.reset());
        const { email, token, newEmail } = this;
        this.changeEmail({
          email,
          token,
          newEmail,
        });
      },
    },
  };
</script>
