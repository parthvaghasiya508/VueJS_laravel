<template>
  <div class="lang-selector">
    <span class="arrow" :class="{ disabled: !scrollLeftEnabled }" @click="scrollLeft">
      <icon stroke-width="1" width="7" height="45" type="arrow-left"/>
    </span>
    <div class="lang-container" ref="container">
      <overlay-scrollbars
        ref="langs"
        :options="langsScrollOptions"
      >
        <div class="lang-list">
          <div class="lang-item" v-for="lang in langs" :key="`lang-${lang}`"
               :class="{ selected: isSelected(lang), valid: isValid(lang) }" @click="select(lang)">
            {{ $t(`langs.${lang}`) }}
            <icon v-if="isValid(lang)" width="18" height="18" type="ok-tick"/>
            <icon v-else width="14" height="14" type="history"/>
          </div>
        </div>
      </overlay-scrollbars>
    </div>
    <span class="arrow" :class="{ disabled: !scrollRightEnabled }" @click="scrollRight">
      <icon stroke-width="1" width="7" height="45" type="arrow-right"/>
    </span>
    <!--
    <b-btn v-if="edit" variant="outline-primary" class="langs-edit-button"
           :disabled="disabled" @click="$emit('edit')">
      <icon width="13" height="13" type="edit"/>
      {{ $t('buttons.edit-langs') }}
    </b-btn>
    -->
  </div>
</template>

<script>
  import { langCodes } from '@/shared';

  export default {
    name: 'LangSelector',
    props: {
      value: {
        required: true,
      },
      valid: {
        required: false,
      },
      edit: {
        default: true,
      },
      disabled: {
        default: false,
      },
    },
    data() {
      return {
        langsScrollOptions: {
          sizeAutoCapable: true,
          clipAlways: true,
          callbacks: {
            onOverflowChanged: this.resetScroller,
            onOverflowAmountChanged: this.resetScroller,
            onScroll: this.updateScrollButtons,
          },
        },
        scrollLeftEnabled: false,
        scrollRightEnabled: false,
      };
    },
    computed: {
      langs: {
        get: () => langCodes,
      },
    },
    methods: {
      resetScroller() {
        this.$nextTick(() => {
          const inst = this.$refs.langs.osInstance();
          if (inst == null) return;
          inst.scroll({ x: 1 }, 1, null, () => {
            inst.scroll({ x: 0 }, 1);
          });
          this.updateScrollButtons();
        });
      },
      updateScrollButtons() {
        const inst = this.$refs.langs.osInstance();
        const scroll = inst.scroll();
        this.scrollLeftEnabled = scroll.position.x > 0;
        this.scrollRightEnabled = scroll.position.x < scroll.max.x;
      },
      scrollLeft() {
        const w = this.$refs.container.getBoundingClientRect()?.width || '35%';
        const inst = this.$refs.langs.osInstance();
        inst.scroll({ x: `-= ${w}` }, 350);
      },
      scrollRight() {
        const w = this.$refs.container.getBoundingClientRect()?.width || '35%';
        const inst = this.$refs.langs.osInstance();
        inst.scroll({ x: `+= ${w}` }, 350);
      },
      isSelected(lang) {
        return this.value != null && this.value === lang;
      },
      select(lang) {
        this.$emit('input', lang);
      },
      isValid(lang) {
        return Array.isArray(this.valid) && this.valid.includes(lang);
      },
    },
  };
</script>
