<template>
  <div class="page-channels-health">
    <div class="panel-title position-relative w-100 title">
      <p>{{ $t('pages.channelshealth.title') }}</p>
    </div>
    <channels-health/>
  </div>
</template>

<script>
  import ChannelsHealth from '@/components/Pages/ChannelsHealth.vue';

  export default {
    name: 'ChannelsHealthPersons',
    components: { ChannelsHealth },
  };
</script>
