<template>
  <div class="guests-price-wrapper">
    <div>
      <p>{{ $t('guests-prices.guests') }}</p>
      <p v-for="{ cnt } in grows" :key="`grow-${cnt}`">
        <icon width="11" height="12" v-for="i in iconsCount(cnt)" :key="`gi-${cnt}-${i}`" type="user"/>
        <template v-if="moreIcons(cnt)">
          &times; {{ cnt }}
        </template>
      </p>
    </div>
    <div>
      <p>{{ $tc('guests-prices.price', 1) }}</p>
      <p class="price" v-for="(r, idx) in grows" :key="`gprice-${idx}`">
        <template v-if="r.price != null">
          {{ r.price }} {{ currency }}
          <span v-if="min(r.cnt)">{{ $t('guests-prices.min') }}</span>
          <span v-else-if="max(r.cnt)">{{ $t('guests-prices.max') }}</span>
        </template>
        <template v-else>&mdash;</template>
      </p>
    </div>
  </div>
</template>

<script>
  export default {
    name: 'GuestsPriceMultiple',
    props: {
      occupancy: {
        type: Object,
        required: true,
      },
      surcharge: {
        type: Object,
        required: true,
      },
      reduction: {
        type: Object,
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
      grows() {
        const ret = [];
        let { min, max, std } = this.occupancy;
        min = Math.max(parseInt(min, 10), 1);
        max = parseInt(max, 10);
        std = parseInt(std, 10);
        for (let i = max; i >= min; i -= 1) {
          if (i !== std) {
            ret.push({
              cnt: i,
              price: this.priceFor(i),
            });
          }
        }
        return ret;
      },
      priceValid() {
        // eslint-disable-next-line no-restricted-globals
        return !isNaN(this.price) && this.price != null && this.price > 0;
      },
    },
    methods: {
      min(gcount) {
        return this.occupancy.min - gcount === 0;
      },
      max(gcount) {
        return this.occupancy.max - gcount === 0;
      },
      iconsCount(gcount) {
        return gcount < 4 ? gcount : 1;
      },
      moreIcons(gcount) {
        return gcount > 3;
      },
      priceFor(gcount) {
        if (!this.priceValid) return null;
        const { std } = this.occupancy;
        const cnt = gcount - std;
        const block = cnt < 1 ? this.reduction : this.surcharge;
        let c = parseFloat(this.price) || 0;
        const v = parseFloat(block.value) || 0;
        if (block.mode === 'amount') {
          c += cnt * v;
        } else {
          c *= 1 + (cnt * (v / 100.0));
        }
        c = parseFloat(c.toFixed(2));
        return c;
      },
    },
  };
</script>
