<template>
  <div class="page-roomtypes">
    <div class="panel-title position-relative w-100 title">
      <p>{{ $t('pages.roomtypes.title') }}</p>
    </div>
    <room-types/>
  </div>
</template>

<script>
  import RoomTypes from '@/components/Pages/RoomTypes.vue';

  export default {
    name: 'PageRoomTypes',
    components: { RoomTypes },
  };
</script>
