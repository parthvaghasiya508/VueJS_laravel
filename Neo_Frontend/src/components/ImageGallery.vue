<template>
  <div class="image-gallery-group">
    <div class="drop-box">
      <DropAnImage :room="room.pid" />
    </div>
    <div class="img-gallery">
      <div class="img-gallery-header">
        {{ room.langs.en.name }}
        <DropAnImage :room="room.pid" tiny class="dropper-md" />
        <div class="images-scroller">
          <span class="image-scroll" :class="{ disabled: !scrollLeftEnabled }" @click="scrollLeft">
            <icon class="arrow" stroke-width="1" width="13" height="22" type="arrow-left"/>
          </span>
          <span class="image-scroll" :class="{ disabled: !scrollRightEnabled }" @click="scrollRight">
            <icon class="arrow" stroke-width="1" width="13" height="22" type="arrow-right"/>
          </span>
        </div>
      </div>
      <div class="img-gallery-content">
        <overlay-scrollbars
          ref="galleryScroll"
          :options="datesScrollOptions"
          class="scroll-bar"
        >
          <draggable class="gallery-item" drag-class="dragging" ghost-class="ghost" :group="listGroup"
                     @start="reorder=true" @end="reorder=false" :class="{ reorder }" v-model="sortedImages"
          >
            <div v-for="image in roomImages" :key="`${room.pid}-${image.id}`"
                 :data-id="image.id" @click="$emit('select', image.id)">
              <div class="item"
                   :style="{ backgroundImage: `url(${image.url})` }">
                <div class="item-shadow">
                  <div class="drag-item">
                    <icon width="35" height="35" type="drag-drop"/>
                    <div class="drag-move">{{ $t('pages.photos.library.dragnmove') }}</div>
                    <div>{{ $t('pages.photos.library.or-click-to-open') }}</div>
                  </div>
                </div>
                <div class="resolution-mark" v-if="image.lowres">
                  <icon width="16" height="16" type="i-mark"/>
                </div>
              </div>
              <div class="resolution">
                <div class="resolution-size" v-if="image.lowres">
                  {{ $t('pages.photos.library.low-resolution') }} {{ image.orig_width }}&times;{{ image.orig_height }}
                </div>
              </div>
              <div class="item-option">
                <div class="edit onhover" @click="selectField(room.id, image.id)">
                  <icon width="12" height="12" type="edit"/>
                </div>
                <div>
                  <input type="text" class="edit-name" :ref="`name-${room.id}-${image.id}`" disabled
                         @blur="deselectField"
                         @keyup.esc="resetField"
                         @keyup.enter="updateField"
                         :value="image.display_name"
                  />
                </div>
                <div class="delete onhover" @click="deleteImage(image.id)">
                  <icon width="14" height="17" type="delete"/>
                </div>
              </div>
            </div>
          </draggable>
        </overlay-scrollbars>
      </div>
    </div>
    <DropAnImage :room="room.pid" tiny class="dropper-sm" />
  </div>
</template>

<script>
  import { mapActions, mapGetters } from 'vuex';
  import draggable from 'vuedraggable';
  import DropAnImage from '@/components/DropAnImage.vue';

  export default {
    name: 'ImageGallery',
    components: { draggable, DropAnImage },
    data() {
      return {
        datesScrollOptions: {
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
        uploadPercentage: 0,
        imageBoxWidth: 0,
        wrongFile: false,
        imageSource: null,
        selectedField: null,
        reorder: false,
      };
    },
    props: {
      room: {
        type: Object,
        required: true,
      },
    },
    computed: {
      ...mapGetters('neogallery', ['images']),

      roomImages() {
        return this.room.images
          .map((iid) => this.images.find(({ id }) => id === iid))
          .filter((i) => i != null);
      },
      sortedImages: {
        get() {
          return this.roomImages.map(({ id }) => id);
        },
        async set(images) {
          const { pid } = this.room;
          await this.reorderImages({ pid, images });
        },
      },
      listGroup() {
        return {
          name: 'rooms',
          pull(to, from, el, ev) {
            if (to === from) return true;
            const { id } = el.dataset;
            const ids = [...to.el.children].map((c) => c.dataset.id);
            if (ids.includes(id)) return false;
            return ev.altKey ? 'clone' : true;
          },
          put(to, from, el) {
            if (to === from) return true;
            const { id } = el.dataset;
            const ids = [...to.el.children].map((c) => c.dataset.id);
            return !ids.includes(id);
          },
        };
      },
    },
    methods: {
      ...mapActions('neogallery', [
        'updateName', 'uploadImage', 'deleteImage', 'reorderImages',
      ]),

      resetScroller() {
        this.$nextTick(() => {
          const inst = this.$refs.galleryScroll.osInstance();
          if (inst == null) return;
          const state = inst.getState();
          if (state.hasOverflow.x) {
            inst.scroll({ x: 0 });
          }
          this.updateScrollButtons();
        });
      },
      updateScrollButtons() {
        const inst = this.$refs.galleryScroll.osInstance();
        const scroll = inst.scroll();
        const empty = !this.room.images.length;
        this.scrollLeftEnabled = !empty && scroll.position.x > 0;
        this.scrollRightEnabled = !empty && scroll.position.x < scroll.max.x;
        (this.$refs.roomRows || []).forEach((r) => {
          (window.requestAnimationFrame || window.mozRequestAnimationFrame || window.webkitRequestAnimationFrame)(
            () => {
              // eslint-disable-next-line no-param-reassign
              r.scrollLeft = scroll.position.x;
            },
          );
        });
      },
      scrollLeft() {
        const inst = this.$refs.galleryScroll.osInstance();
        inst.scroll({ x: '-= 256' }, 350);
      },
      scrollRight() {
        const inst = this.$refs.galleryScroll.osInstance();
        inst.scroll({ x: '+= 256' }, 350);
      },
      imageById(id) {
        return this.images.find((i) => i.id === id);
      },
      selectField(rid, iid) {
        const input = this.$refs[`name-${rid}-${iid}`][0];
        const item = input.parentNode.parentNode;
        this.selectedField = { rid, iid };
        item.classList.add('selected');
        input.disabled = false;
        this.$nextTick(() => {
          input.focus();
          input.select();
          input.scrollLeft = 0;
        });
      },
      deselectField(ev) {
        const input = ev.target;
        const item = input.parentNode.parentNode;
        const original = this.imageById(this.selectedField.iid).display_name;
        if (input.value !== original) {
          input.value = original;
        }
        this.selectedField = null;
        window.getSelection().removeAllRanges();
        input.disabled = true;
        this.$nextTick(() => {
          input.scrollLeft = 0;
        });
        item.classList.remove('selected');
      },
      resetField(ev) {
        ev.target.blur();
      },
      updateField(ev) {
        const input = ev.target;
        const name = input.value;
        const original = this.imageById(this.selectedField.iid).display_name;
        if (name === original) {
          input.blur();
          return;
        }
        const id = this.selectedField.iid;
        this.updateName({ id, name });
        input.blur();
      },
    },
  };
</script>
