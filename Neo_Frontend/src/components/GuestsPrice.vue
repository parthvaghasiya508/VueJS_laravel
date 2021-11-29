<template>
  <div class="guests-price-wrapper">
    <div>
      <p>{{ $t('guests-prices.guests') }}</p>
      <p>
        <icon width="11" height="12" v-for="i in iconsCount" :key="i" type="user"/>
        <template v-if="moreIcons">
          &times; {{ g }}
        </template>
      </p>
    </div>
    <div>
      <p>{{ priceTitle }}</p>
      <p class="price">
        <template v-if="priceValid">
          {{ price }} {{ currency }}
          <span v-if="min">{{ $t('guests-prices.min') }}</span>
          <span v-else-if="max">{{ $t('guests-prices.max') }}</span>
        </template>
        <template v-else>&mdash;</template>
      </p>
    </div>
  </div>
</template>

<script>
  export default {
    name: 'GuestsPrice',
    props: {
      std: {
        type: Boolean,
        default: false,
      },
      min: {
        type: Boolean,
        default: false,
      },
      max: {
        type: Boolean,
        default: false,
      },
      guests: {
        type: [Number, String],
        required: true,
      },
      currency: {
        validator: (prop) => ['string', 'undefined'].includes(typeof prop),
        required: true,
      },
      price: {
        validator: (prop) => ['object', 'number', 'string', 'undefined'].includes(typeof prop),
        required: true,
      },
    },
    computed: {
      g() {
        return parseInt(this.guests, 10);
      },
      priceTitle() {
        return this.$tc('guests-prices.price', 1 + this.std);
      },
      priceValid() {
        // eslint-disable-next-line no-restricted-globals
        return !isNaN(this.price) && this.price != null && this.price > 0;
      },
      iconsCount() {
        const { g } = this;
        return g < 4 ? g : 1;
      },
      moreIcons() {
        return this.g > 3;
      },
    },
  };
</script>
