<template>
  <div class="avatar-selector-container"
       @dragover.prevent="dragOver"
       @dragleave.prevent="dragLeave"
       @drop.prevent="drop">
    <div :class="getClasses">
      <img v-if="!isEmpty" ref="preview" :src="imageUrl" />
      <span v-if="loading"><spinner/></span>
      <span @click="openUpload" class="camera" v-else>
        <icon type="camera" width="22" height="22" /></span>
    </div>
    <div class="controls" v-show="!loading">
      <b-btn variant="primary" class="btn-avatar-nav" v-if="isEmpty" size="sm" @click="openUpload">
        {{ $t('pages.masterdata.button-add-logo') }}
      </b-btn>
      <template v-else>
        <b-btn variant="primary" class="btn-avatar-nav" size="sm" @click="openUpload">
          {{ $t('pages.masterdata.button-change-logo') }}
        </b-btn>
        <b-btn variant="danger" size="sm" @click="removeImage" class="mr-1 btn-avatar-nav">
          {{ $t('buttons.delete') }}
        </b-btn>
      </template>
    </div>
    <input type="file" ref="upload" name="upload" accept="image/png,image/jpeg" @change="fileSelected"/>
  </div>
</template>

<script>

  export default {
    name: 'AvatarSelector',
    data() {
      return {
        isDragging: false,
        preview: null,
        remove: false,
        loading: false,
      };
    },
    props: {
      value: {
        required: true,
        validator: (prop) => prop == null || ['object'].includes(typeof prop),
      },
    },
    watch: {
      value(v) {
        if (v.upload == null) {
          this.preview = null;
          this.previewFile = null;
        }
        this.remove = v.remove;
      },
    },
    computed: {
      getClasses() {
        return { dragging: this.isDragging, empty: this.isEmpty };
      },
      isEmpty() {
        return this.preview == null && (this.original == null || this.remove);
      },
      imageUrl() {
        return this.preview != null ? this.preview : this.original;
      },
      original() {
        return this.value != null && this.value.original != null ? this.value.original : null;
      },
    },
    methods: {
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
        this.setImage(file);
      },
      fileSelected(ev) {
        const file = [...ev.target.files].shift();
        if (!file) return;
        this.setImage(file);
      },
      setImage(file) {
        this.$refs.upload.value = null;
        this.loading = true;
        const reader = new FileReader();
        reader.onload = (e) => {
          this.preview = e.target.result;
          this.previewFile = file;
          this.remove = false;
          this.updateValue();
          this.loading = false;
        };
        this.$nextTick(() => {
          reader.readAsDataURL(file);
        });
      },
      removeImage() {
        this.preview = null;
        this.previewFile = null;
        this.remove = true;
        this.updateValue();
      },
      updateValue() {
        this.$emit('input', {
          ...(this.value || {}),
          upload: this.previewFile,
          remove: this.remove,
        });
      },
    },
  };
</script>
