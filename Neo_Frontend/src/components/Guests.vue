<template>
  <div class="guests-wrapper">
    <icon width="11" height="12" v-for="i in iconsCount" :key="i" type="user"/>
    <template v-if="moreIcons">
      &times; {{ g }}
    </template>
  </div>
</template>

<script>
  export default {
    name: 'Guests',
    props: {
      guests: {
        validator: (v) => v == null || ['number', 'string'].includes(typeof v),
        required: true,
      },
      max: {
        type: [Number, String],
        default: 0,
      },
    },
    computed: {
      g() {
        const g = parseInt(this.guests, 10);
        const max = parseInt(this.max, 10);
        return Number.isNaN(g) || (max > 0 && max < g) ? 0 : g;
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
