<template>
  <div class="page-payment">
    <div class="top-info">
      <h2>{{ $t('pages.payments.heading') }}</h2>
      <p>{{ $t('pages.payments.subtitle') }}</p>
    </div>
    <spinner v-if="pending" />
    <div class="payment-methods" v-if="!pending">
      <!-- SEPA -->
      <b-card
        id="show-btn"
        class="card-modal"
        @click="openSepaModal()"
      >
        <active-payment-badge v-if="defaultMethod === 'sepa_debit'" />
        <img src="/assets/icons/sepa.svg" alt="sepa logo" />
      </b-card>
      <sepa-modal
        ref="sepaModal"
        @validateMethod="onValidatePaymentMethod"
      />


      <!-- Bank transfer -->
      <b-card
        @click="openBankTransferModal()"
      >
        <active-payment-badge v-if="defaultMethod === 'bancontact'" />
        <h5> {{ $t('pages.payments.methods.bank-transfer') }} </h5>
        <icon width="45" height="42" type="bank-icon" />
      </b-card>
      <bank-transfer-modal
        ref="bankTransferModal"
        @validateMethod="onValidatePaymentMethod"
      />

      <!-- Credit Card -->
      <b-card
        class="card-modal"
        @click="openCreditCardModal()"
      >
        <h5>
          {{$t('pages.payments.methods.credit-card')}}
        </h5>
        <active-payment-badge v-if="defaultMethod === 'card'" />
        <icon width="45" height="35" type="credit-card"/>
      </b-card>
      <credit-card-modal
        ref="creditCardModal"
        @validateMethod="onValidatePaymentMethod"
      />

    </div>
  </div>
</template>

<script>
  import ActivePaymentBadge from '@/components/PaymentMethods/ActivePaymentBadge.vue';
  import SepaModal from '@/components/PaymentMethods/SepaModal.vue';
  import CreditCardModal from '@/components/PaymentMethods/CreditCardModal.vue';
  import BankTransferModal from '@/components/PaymentMethods/BankTransferModal.vue';
  import { mapActions, mapGetters, mapState } from 'vuex';
  import Vue from 'vue';
  import { StripePlugin } from '@vue-stripe/vue-stripe';

  export default {
    name: 'Payments',
    components: {
      ActivePaymentBadge,
      SepaModal,
      CreditCardModal,
      BankTransferModal,
    },
    async created() {
      await this.fetchData();
    },
    data() {
      return {
      };
    },
    mounted() {
      if (process.env.VUE_APP_STRIPE_ENABLED === 'true') {
        Vue.use(StripePlugin, {
          pk: process.env.VUE_APP_STRIPE_KEY,
        });
      }
    },
    computed: {
      ...mapState('payments', ['pending']),
      ...mapGetters('payments', ['loaded', 'paymentMethods', 'defaultPaymentMethod', 'intent']),

      defaultMethod() {
        return this.defaultPaymentMethod?.type;
      },
    },
    methods: {
      ...mapActions('payments', ['fetchData']),

      openSepaModal() {
        if (!this.pending) {
          this.$refs.sepaModal.$refs.modal.show();
        }
      },
      openCreditCardModal() {
        if (!this.pending) {
          this.$refs.creditCardModal.$refs.modal.show();
        }
      },
      openBankTransferModal() {
        if (!this.pending) {
          this.$refs.bankTransferModal.$refs.modal.show();
        }
      },
      async onValidatePaymentMethod() {
        await this.fetchData();
      },
    },
  };
</script>
