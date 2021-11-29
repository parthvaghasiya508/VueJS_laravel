<template>
  <div class="panel panel-form" v-if="!sending">
    <div class="panel-body">
      <img class="panel-image" src="/assets/images/envelop.svg" alt="Envelop image"/>
      <h1>{{ $t('signup.verify.heading') }}</h1>
      <p>{{ $t('signup.verify.tip') }}</p>
      <div>
        <h3>{{ user.email }}</h3>
        <a class="d-none" href="#">Change email address</a>
      </div>
      <div class="alert alert-success mt-4" role="alert" v-if="emailResent">
        {{ $t('signup.verify.email-resent') }}
      </div>
      <div class="alert alert-danger" role="alert" v-if="rateLimitError">
        {{ rateLimitError.message }}
      </div>
    </div>
    <div class="panel-footer mt-xl">
      <p class="mb-0">
        <i18n path="signup.verify.resend" :tag="false">
          <template #resend>
            <b-link @click.prevent="resendEmail"
                    :disabled="emailResent">{{ $t('signup.verify.button-resend') }}</b-link>
          </template>
        </i18n>
      </p>
    </div>
  </div>
</template>

<script>
  import { mapGetters, mapActions } from 'vuex';

  export default {
    name: 'VerifyEmailNotice',
    data() {
      return {
        sending: false,
      };
    },
    computed: {
      ...mapGetters('user', ['user', 'emailResent', 'rateLimitError']),
    },
    methods: {
      ...mapActions('user', ['resendEmail', 'verifyEmail']),
    },
    created() {
      // check for verification link
      const { id, hash } = this.$route.params;
      // eslint-disable-next-line no-bitwise
      if ((id != null) ^ (hash != null)) {
        this.$router.replace({ name: this.$route.name });
        return;
      }
      if (id) {
        this.sending = true;
        // send verify email request
        const { query } = this.$route;
        this.verifyEmail({
          id,
          hash,
          query,
        });
      }
    },
  };
</script>
