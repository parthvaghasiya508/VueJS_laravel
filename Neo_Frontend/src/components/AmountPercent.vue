<template>
  <div class="amount-percent-wrapper">
    <div class="amount-percent-field" :data-symbol="shownSymbol"
         :class="[`mode-${mode}`, { disabled, simple, prefixed, positive, negative, smbshown: symbolShown }]">
      <ValidatedField
        type="number" :id="$uid" :name="$uid" class="mb-0" :no-icon="noIcon" :no-tooltip="noTooltip"
        :value="modeValue" :disabled="disabled" @input="onInput($event)"
        :min="min" :max="max" :rules="finalRules" ref="field" purenumber :integer="isPercent || integer"
        :placeholder="placeholder"
      />
    </div>
    <template v-if="!simple">
      <b-btn variant="square" :class="{ active: isPercent }" :disabled="disabled"
             @click="changeMode('percent')">%</b-btn>
      <b-btn variant="square" :class="{ active: isAmount }" :disabled="disabled"
             @click="changeMode('amount')">{{ shownSymbol }}</b-btn>
    </template>
  </div>
</template>

<script>
  import { mapGetters } from 'vuex';
  import { currencySymbols } from '@/shared';


  export default {
    name: 'AmountPercent',
    props: {
      value: {
        // type: [Object, undefined],
        required: true,
        validator: (prop) => ['object', 'number', 'string', 'undefined'].includes(typeof prop),
      },
      price: {
        type: [Number, String],
        default: null,
      },
      result: {
        type: [Number, String],
        default: null,
      },
      rules: {
        validator: (prop) => (prop == null || ['object', 'string'].includes(typeof prop)),
        default: null,
      },
      positive: {
        type: Boolean,
        default: false,
      },
      negative: {
        type: Boolean,
        default: false,
      },
      simple: {
        type: [Boolean, String],
        default: false,
      },
      placeholder: {
        type: String,
        default: '',
      },
      symbol: {
        type: String,
        default: null,
      },
      integer: {
        type: Boolean,
        default: false,
      },
      preventNegativePrice: {
        type: Boolean,
        default: false,
      },
      preventInvalidPrice: {
        type: Boolean,
        default: false,
      },
      required: {
        type: Boolean,
        default: false,
      },
      disabled: {
        type: Boolean,
        default: false,
      },
      acceptZero: {
        type: Boolean,
        default: false,
      },
      noIcon: {
        type: Boolean,
        default: true,
      },
      noTooltip: {
        type: Boolean,
        default: true,
      },
    },
    mounted() {
      this.propagateResult();
    },
    computed: {
      ...mapGetters('user', ['currency']),
      mode() {
        if (this.simple) {
          return this.simple === 'percent' ? 'percent' : 'amount';
        }
        return this.value.mode || 'amount';
      },
      shownSymbol() {
        return this.symbol != null ? this.symbol : currencySymbols[this.currency];
      },
      isAmount() {
        return this.mode === 'amount';
      },
      isPercent() {
        return this.mode === 'percent';
      },
      isOverflow() {
        return this.negative && this.price != null && this.preventNegativePrice && this.result < 0;
      },
      isInvalid() {
        return this.negative && this.price != null && this.preventInvalidPrice && this.result <= 0;
      },
      modeValue() {
        return (this.simple ? this.value : this.value.value) || '';
      },
      min() {
        let v = 0;
        if (!this.acceptZero) {
          v = this.isPercent || this.integer ? 1 : 0.01;
        }
        return v;
      },
      max() {
        return this.isPercent || this.integer ? 100 : 999999999.99;
      },
      finalRules() {
        return this.rules != null && this.rules ? this.rules : {
          required: this.required,
          between: { min: this.min, max: this.max },
          numeric: this.isPercent || this.integer,
          fail: this.isOverflow || this.isInvalid,
        };
      },
      prefixed() {
        return (`${this.modeValue}` || '').trim() && parseFloat(this.modeValue) !== 0
          /* && !this.disabled */ && (this.positive || this.negative);
      },
      symbolShown() {
        return (`${this.modeValue}` || '').trim() && parseFloat(this.modeValue) !== 0;
      },
    },
    watch: {
      price() {
        this.propagateResult();
      },
      disabled(val) {
        if (!val) {
          this.propagateResult();
        }
      },
    },
    methods: {
      propagateResult() {
        if (this.price != null) {
          // calculate
          this.$nextTick(() => {
            this.$emit('update:result', this.calculate(this.price));
          });
        }
      },
      calculate(price) {
        let c = parseFloat(price) || 0;
        // eslint-disable-next-line no-nested-ternary
        const k = this.negative ? -1 : (this.positive ? 1 : 0);
        const v = parseFloat(this.modeValue) || 0;
        if (this.isAmount) {
          c += k * v;
        } else {
          c *= 1 + (k * (v / 100.0));
        }
        c = parseFloat(c.toFixed(2));
        return c;
      },
      onInput(value, mode = null) {
        if (this.simple) {
          this.$emit('input', value);
        } else {
          this.$emit('input', {
            mode: mode || this.mode,
            value,
          });
        }
        this.propagateResult();
      },
      changeMode(mode) {
        // this.$emit('update:mode', mode);
        this.onInput('', mode);
        this.$nextTick(() => {
          this.$refs.field.$emit('reset');
          this.$refs.field.$emit('focus');
        });
      },
    },
  };
</script>
