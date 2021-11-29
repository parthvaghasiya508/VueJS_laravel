<template>
  <b-form-input
    type="tel"
    autocomplete="off"
    :placeholder="placeholder"
    :name="name"
    :state="state"
    :value="value"
    @input="updateNumber"
    @countrychange="updateNumber"
    :disabled="disabled"
  />
</template>

<script>
  import intlTelInput from 'intl-tel-input';
  import { mapGetters } from 'vuex';

  const utilsScript = import('intl-tel-input/build/js/utils');

  // window.intlTelInputGlobals.getCountryData().forEach((cd) => {
  //   // eslint-disable-next-line no-param-reassign
  //   cd.name = cd.name.replace(/.+\((.+)\)/, '$1');
  // });

  export default {
    name: 'TelInput',
    props: {
      value: {
        required: true,
      },
      placeholder: {
        type: String,
        default: '',
      },
      name: {
        type: String,
        required: true,
      },
      state: {
        required: false,
      },
      nationalMode: {
        type: Boolean,
        default: false,
      },
      disabled: Boolean,
    },
    data() {
      return {
        iti: null,
      };
    },
    mounted() {
      this.iti = intlTelInput(this.$el, {
        utilsScript,
        nationalMode: this.nationalMode,
        formatOnDisplay: true,
        initialCountry: this.$te('tel-iso') ? this.$t('tel-iso') : 'de',
      });
      if (this.value != null) {
        this.iti.setNumber(this.value);
      }
    },
    beforeDestroy() {
      if (this.iti) {
        this.iti.destroy();
        this.iti = null;
      }
    },
    watch: {
      value() {
        this.iti.setNumber(this.value != null ? this.value : '');
      },
    },
    computed: {
      ...mapGetters('user', ['lang']),
    },
    methods: {
      updateNumber() {
        this.$emit('input', this.iti.getNumber());
        this.$emit('valid', this.iti.isValidNumber());
        this.$emit('error', this.$t(`errors.tel.${this.iti.getValidationError()}`));
      },
    },
  };
</script>

<style scoped>

</style>
