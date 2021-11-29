<template>
  <div class="component-neogallery">
    <div class="panel">
      <div class="position-relative panel-title title" :class="titleClass">
        <p>{{ $t('pages.photos.title') }}</p>
        <b-btn variant="outline-primary" v-b-modal.mediaLibraryModal
               @click="resetLibraryModal">{{ $t('pages.photos.button-library') }}</b-btn>
      </div>
      <div class="gallery gallery-content" v-if="loaded">
        <div class="gallery-list" v-for="room in rooms" :key="room.id">
          <ImageGallery :room="room" @select="showImage"/>
        </div>
      </div>
    </div>
    <b-modal
      no-close-on-backdrop
      id="mediaLibraryModal"
      ref="mediaLibraryModal"
      modal-class="media-library-modal"
      size="xxl"
      no-fade
      centered
      hide-footer
      :hide-header-close="updating"
      :no-close-on-esc="updating"
    >
      <template #modal-header-close>
        <icon width="20" height="20" class="d-none d-md-block" type="times"/>
        <icon width="10" height="18" class="d-md-none" type="arrow-left"/>
      </template>
      <template #modal-title>
        <div class="">
          <div>{{ $t('pages.photos.library.title') }}</div>
          <div class="filter-option">
            <div class="resolution-filter">
              <check-box v-model="lowresOnly">{{ $t('pages.photos.library.lowres-only') }}</check-box>
            </div>
            <search-filter v-model="searchQuery" :placeholder="$t('pages.photos.library.search')" noborder />
          </div>
        </div>
      </template>
      <ValidationObserver>
        <div class="media-body">
          <div class="media">
            <MediaGallery @details="showDetails"
                          :lowres="lowresOnly"
                          :search="searchQuery"
                          :selected="details.id"
            />
          </div>
          <div class="media-detail-backdrop" :class="{ active: details.id != null }"></div>
          <div class="media-detail" :class="{ active: details.id != null }">
            <div v-if="details.id!=null" class="detail-image" :style="{ backgroundImage: `url(${details.url})` }"></div>
            <div class="detail-decription" v-if="details.id!=null">
              <div class="detail-info">
                <div class="name-info">
                  <div class="real-name">
                    <div>{{ $t('pages.photos.library.name') }}</div>
                    <div class="room-name real">
                      {{ details.name }}
                    </div>
                  </div>
                  <div class="display-name">
                    <div>{{ $t('pages.photos.library.display-name') }}</div>
                    <div class="room-name display">
                      <b-btn variant="icon" class="room-edit"
                             @click="selectNameField" :disabled="imageControlsDisabled">
                        <icon width="12" height="12" type="edit"/>
                      </b-btn>
                      <input type="text" class="edit-name" ref="editName" disabled
                             @blur="deselectNameField"
                             @keypress.esc.stop
                             @keydown.esc.stop
                             @keyup.esc.stop="resetNameField"
                             @keyup.enter="updateNameField"
                             :value="details.display_name"
                      />
                    </div>
                  </div>
                </div>
                <div class="image-info">
                  <div class="created-date">{{ createDate(details.created_at) }}</div>
                  <div class="image-size">{{ imageSize(details.size) }}</div>
                  <div class="image-resolution" :class="{ lowres: details.lowres }">
                    {{ details.orig_width }}x{{ details.orig_height }}
                    <div class="low-resolution">
                      {{ $t('pages.photos.library.low-resolution') }}
                    </div>
                  </div>
                  <b-btn variant="icon" class="image-delete"
                         @click="deleteImage(details.id)" :disabled="imageControlsDisabled">
                    <icon class="room-delete" width="14" height="17" type="delete"/>
                  </b-btn>
                </div>
              </div>
              <div class="detail-relatives">
                {{ $t('pages.photos.library.related-to-rooms') }}
              </div>
              <div class="related-rooms" ref="relatedRooms">
                <div class="rooms" v-for="room in rooms" :key="room.id">
                  <check-box v-model="details.rooms" :val="room.pid"
                             :disabled="imageControlsDisabled"
                  >{{ room.langs.en.name }}
                  </check-box>
                </div>
              </div>
              <div class="control-btn">
                <b-btn variant="outline-primary" @click="details = {}"
                       :disabled="imageControlsDisabled">{{ $t('buttons.cancel') }}</b-btn>
                <b-btn variant="secondary" @click="updateImageRooms"
                       :disabled="imageControlsDisabled">{{ $t('buttons.save') }}</b-btn>
              </div>
            </div>
            <div class="detail-empty" v-else>{{ $t('pages.photos.library.no-image-selected') }}</div>
          </div>
        </div>
      </ValidationObserver>
    </b-modal>
    <b-alert v-if="pmsError" variant="danger" show>
      <h4 class="alert-heading">{{ $t('error') }}</h4>
      <p class="mb-0">{{ pmsError.response ? pmsError.response.data.message : pmsError }}</p>
    </b-alert>
  </div>
</template>

<script>
  import { mapState, mapGetters, mapActions } from 'vuex';
  import moment from 'moment';
  import numeral from 'numeral';

  import ImageGallery from '@/components/ImageGallery.vue';
  import MediaGallery from '@/components/MediaGallery.vue';

  export default {
    name: 'Photos',
    components: { ImageGallery, MediaGallery },
    props: {
      titleClass: {
        validator: (v) => ['object', 'string', 'undefined'].includes(typeof v),
      },
    },
    data() {
      return {
        searchQuery: '',
        lowresOnly: false,
        details: {},
        selectedName: false,
      };
    },
    async created() {
      await this.fetchData();
      // this.$refs.mediaLibraryModal.show();
    },
    computed: {
      ...mapState('neogallery', ['pending', 'pmsError', 'updating', 'updatingImage', 'updatingRoom']),
      ...mapGetters('neogallery', ['loaded', 'rooms', 'images']),

      imageControlsDisabled() {
        return this.updatingImage === this.details.id;
      },
    },
    watch: {
      images() {
        const { id } = this.details;
        if (id == null) return;
        const ids = this.images.map(({ iid }) => iid);
        if (!ids.includes(id)) this.details = {};
      },
    },
    methods: {
      ...mapActions('neogallery', ['fetchData', 'updateName', 'updateRooms', 'deleteImage']),

      resetLibraryModal() {
        this.details = {};
        this.lowresOnly = false;
        this.searchQuery = '';
      },
      showDetails(image) {
        this.details = { ...image };
        const cb = () => {
          const rr = this.$refs.relatedRooms;
          if (!rr) {
            this.$nextTick(cb);
          } else {
            rr.scrollTop = 0;
          }
        };
        this.$nextTick(cb);
      },
      createDate(dt) {
        if (dt == null) return '';
        return moment(dt)
          .format('DD MMM YYYY');
      },
      imageSize(size) {
        return numeral(size)
          .format('0b');
      },
      imageById(id) {
        return this.images.find((i) => i.id === id);
      },
      selectNameField() {
        const input = this.$refs.editName;
        const item = input.parentNode.parentNode;
        this.selectedName = true;
        item.classList.add('selected');
        input.disabled = false;
        this.$nextTick(() => {
          input.focus();
          input.select();
          input.scrollLeft = 0;
        });
      },
      deselectNameField(ev) {
        const input = ev.target;
        const item = input.parentNode.parentNode;
        const original = this.imageById(this.details.id).display_name;
        if (input.value !== original) {
          input.value = original;
        }
        this.selectedName = false;
        window.getSelection()
          .removeAllRanges();
        input.disabled = true;
        this.$nextTick(() => {
          input.scrollLeft = 0;
        });
        item.classList.remove('selected');
      },
      resetNameField(ev) {
        ev.target.blur();
      },
      updateNameField(ev) {
        const input = ev.target;
        const name = input.value;
        const { id } = this.details;
        const original = this.imageById(id).display_name;
        if (name === original) {
          input.blur();
          return;
        }
        this.updateName({ id, name });
        input.blur();
      },
      async updateImageRooms() {
        const { id, rooms } = this.details;
        const original = this.imageById(id).rooms;
        const same = original.length === rooms.length
          && original.every((iid) => rooms.includes(iid));
        if (!same) {
          await this.updateRooms({ id, rooms });
        }
        this.details = {};
      },
      showImage(id) {
        const image = this.imageById(id);
        if (!image) return;
        this.$refs.mediaLibraryModal.show();
        this.$nextTick(() => {
          this.showDetails(image);
        });
      },
    },
  };
</script>
