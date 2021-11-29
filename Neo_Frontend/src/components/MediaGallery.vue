<template>
  <div class="media-gallery">
    <div class="drop-box">
      <DropAnImage compact class="dropper-lg" />
      <DropAnImage tiny class="dropper-md" />
    </div>
    <GalleryItem v-for="image in getImages"
                 :key="image.id"
                 :image="image"
                 :selected="selected === image.id"
                 @click="$emit('details', image)"
    />
  </div>
</template>
<script>
  import { mapGetters } from 'vuex';
  import DropAnImage from '@/components/DropAnImage.vue';
  import GalleryItem from '@/components/GalleryItem.vue';

  export default {
    name: 'MediaGallery',
    components: { DropAnImage, GalleryItem },
    props: {
      lowres: Boolean,
      search: String,
      selected: {
        validator: (v) => (v == null || ['number', 'string'].includes(typeof v)),
        default: null,
      },
    },
    computed: {
      ...mapGetters('neogallery', ['images']),
      getImages() {
        const s = this.search != null ? this.search.trim().toLowerCase() : false;
        return [...this.images]
          .sort(({ created_at: a }, { created_at: b }) => (
            // eslint-disable-next-line no-nested-ternary
            a > b ? -1 : (a < b ? 1 : 0)
          ))
          .filter(({ lowres }) => (!this.lowres || lowres))
          .filter(({ display_name: dn }) => {
            if (!s) return true;
            const d = dn != null ? dn.trim().toLowerCase() : false;
            return !!d && d.indexOf(s) !== -1;
          });
      },
    },
  };
</script>
