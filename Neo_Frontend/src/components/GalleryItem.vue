<template>
  <div class="media-item" @click="$emit('click')">
    <div class="media-image" :style="{ backgroundImage: `url(${image.url})` }">
      <div class="item-shadow" v-if="image.lowres">
        <div class="drag-item">
          <icon width="26" height="26" type="i-mark"/>
          <div class="drag-move">{{ image.orig_width }}&times;{{ image.orig_height }}</div>
          <div>{{ $t('pages.photos.library.low-resolution') }}</div>
        </div>
      </div>
      <div class="resolution-mark" v-if="image.lowres">
        <icon width="16" height="16" type="i-mark"/>
      </div>
      <div class="sel-mark" v-if="selected">
        <check-box value="1" empty/>
      </div>
    </div>
    <div class="control-option">
      <icon class="room-edit" width="12" height="12" type="edit"/>
      <input type="text" class="room-name" :value="image.display_name" disabled/>
      <icon class="room-delete" width="14" height="17" type="delete"/>
    </div>
  </div>
</template>
<script>
  export default {
    name: 'GalleryItem',
    props: {
      image: {
        type: Object,
        required: true,
      },
      selected: {
        type: Boolean,
        default: false,
      },
    },
    data() {
      return {
        //
      };
    },
  };
</script>
