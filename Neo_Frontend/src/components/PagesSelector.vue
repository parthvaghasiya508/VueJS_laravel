<template>
  <div class="pages-selector-wrapper">
    <div class="item item-all" v-if="showAll && allowedPages && allowedPages.length > 1">
      <p :class="{ 'text-disabled': disabled }">{{ $t('all') }}</p>
      <switcher small :checked="isAllPages" @change="toggleAllPages" :disabled="disabled" class="switcher" />
    </div>
    <template v-for="({ name, cat, sub }) in allowedPages">
      <div class="item" :key="`page-${name}`">
        <p :class="{ 'text-disabled': disabled }">
          <span v-if="sub">•</span>{{ !cat ? $t(`pages.${name}.title`) : $t(`menu.${name}`) }}
        </p>
        <switcher v-if="!cat" small :checked="value" :value="name" @change="$emit('input', $event)"
                  :disabled="disabled" class="switcher" />
        <!--
        <switcher v-else small :checked="catChecked" @change="toggleCat"
                  :disabled="disabled" />
        -->
      </div>
    </template>
  </div>
</template>

<script>
  import { mapActions, mapGetters } from 'vuex';

  export default {
    name: 'PagesSelector',
    inheritAttrs: true,
    props: {
      showAll: {
        type: Boolean,
        default: false,
      },
      mode: {
        type: String,
        required: true,
        validator: (v) => ['group', 'user', 'hotel'].includes(v),
      },
      allowed: {
        required: true,
        validator: (v) => (v == null || Array.isArray(v)),
      },
      value: {
        type: Array,
        required: true,
      },
      disabled: Boolean,
    },
    data: () => ({
      //
    }),
    async created() {
      await this.fetchPages();
    },
    computed: {
      ...mapGetters('data', ['pages']),
      allowedPages() {
        const ret = [];
        const { mode } = this;
        const a = this.pages.filter(({ name, for_hotel: fh }) => (
          this.allowed.includes(name) && (
            mode === 'group' || (mode === 'user' && !fh) || (mode === 'hotel' && fh)
          )
        ));
        let cat = null;
        a.forEach(({ name, category }) => {
          if (category !== cat) {
            cat = category;
            if (cat != null) {
              ret.push({
                name: cat, cat: true, sub: false,
              });
            }
          }
          ret.push({
            name, cat: false, sub: cat != null,
          });
        });
        return ret;
      },
      isAllPages() {
        const count = this.allowedPages.filter(({ cat }) => !cat).length;
        return this.value.length === count;
      },
    },
    methods: {
      ...mapActions('data', ['fetchPages']),
      toggleAllPages() {
        if (this.isAllPages) {
          this.$emit('input', []);
        } else {
          this.$emit('input', this.allowedPages.filter(({ cat }) => !cat).pluck('name'));
        }
      },
    },
  };
</script>
