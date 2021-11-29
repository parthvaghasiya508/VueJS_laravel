<template>
  <div class="drop" :class="{ 'bg-white': isUploading, tiny, compact }"
       @dragover.prevent="dragOver"
       @dragleave.prevent="dragLeave"
       @drop.prevent="drop">
    <template v-if="!tiny">
      <div v-if="!isUploading">
        <div class="drop-cloud" :class="getClasses">
          <icon width="54" height="36" type="cloud"/>
        </div>
        <div class="drag-drop">
          {{ $t('pages.photos.upload.dragndrop') }}
        </div>
        <div class="drag-or-drop">
          {{ $t('pages.photos.upload.or') }}
        </div>
        <b-btn variant="outline-primary" class="drag-drop-btn"
               @click="openUpload">{{ $t('pages.photos.upload.button-browse') }}</b-btn>
      </div>
      <div v-else class="upload-progress">
        <div class="percents">{{ percentsUploaded }}%</div>
        <div class="progress">
          <div class="progress-bar" role="progressbar" :style="{ width: `${percentsUploaded}%` }"
               :aria-valuenow="percentsUploaded" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
      </div>
    </template>
    <div v-else class="drop-add-image" @click="openUpload">
      <icon width="11" height="12" type="plus"/>
      <div class="add">
        {{ $t('buttons.add') }}
      </div>
      <icon width="19" height="16" type="photo"/>
    </div>
    <input type="file" ref="upload" name="upload" accept="image/png,image/jpeg" @change="fileSelected"/>
  </div>
</template>

<script>
  import { mapActions } from 'vuex';

  export default {
    name: 'DropAnImage',
    data() {
      return {
        isDragging: false,
        isUploading: false,
        sizeTotal: 0,
        sizeUploaded: 0,
      };
    },
    props: {
      room: {
        type: String,
        default: null,
      },
      tiny: {
        type: Boolean,
        default: false,
      },
      compact: {
        type: Boolean,
        default: false,
      },
    },
    computed: {
      getClasses() {
        return { dragging: this.isDragging };
      },
      percentsUploaded() {
        if (!this.isUploading || !this.sizeTotal) return 0;
        return Math.ceil((this.sizeUploaded / this.sizeTotal) * 100.0);
      },
    },
    methods: {
      ...mapActions('neogallery', ['uploadImage']),
      openUpload() {
        this.$refs.upload.click();
      },
      dragOver() {
        if (this.isUploading) return;
        this.isDragging = true;
      },
      dragLeave() {
        if (this.isUploading) return;
        this.isDragging = false;
      },
      drop(ev) {
        if (this.isUploading) return;
        this.isDragging = false;
        const file = [...ev.dataTransfer.files].filter((f) => ['image/png', 'image/jpeg'].includes(f.type)).shift();
        if (!file) return;
        this.startUpload(file);
      },
      fileSelected(ev) {
        const file = [...ev.target.files].shift();
        if (!file) return;
        this.startUpload(file);
      },
      async startUpload(file) {
        this.sizeTotal = 0;
        this.sizeUploaded = 0;
        this.isUploading = true;
        const progress = (ev) => {
          this.sizeTotal = ev.total;
          this.sizeUploaded = ev.loaded;
        };
        await this.uploadImage({ file, room: this.room, progress });
        this.sizeTotal = 0;
        this.sizeUploaded = 0;
        this.isUploading = false;
      },
    },
  };
</script>
