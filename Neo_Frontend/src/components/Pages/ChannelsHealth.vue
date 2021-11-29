<template>
  <div class="component-channelshealth">
    <div class="panel position-relative panel-content">
      <div class="list d-none d-md-block left-edge">
        <div class="channelshealth-table" v-if="loaded">
          <table class="w-100">
            <thead>
              <tr>
                <th class="w-position">{{ $t('pages.channelshealth.headers.your-channels') }}</th>
                <th>{{ $t('pages.channelshealth.headers.last-handshake') }}</th>
                <th>{{ $t('pages.channelshealth.headers.activity') }}</th>
              </tr>
            </thead>
            <tbody v-if="noData">
              <tr>
                <td colspan="8" class="w-empty">{{ $t('pages.channelshealth.no-data') }}</td>
              </tr>
            </tbody>
            <tbody v-for="row in channelshealth[0]" :key="row.channelId">
              <tr class="separator before"></tr>
              <tr>
                <td>
                  <a :title="row.channelId">{{ row.channelName ? row.channelName : row.channelId }}</a>
                </td>
                <td>
                  <a :title="row.lastHandShake">{{ row.lastHandShake }}</a>
                </td>
                <td>
                  <a :title="row.activity">{{ row.activity }}</a>
                </td>
              </tr>
              <tr class="separator after"></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
  import { mapActions, mapGetters } from 'vuex';

  export default {
    name: 'ChannelsHealth',
    async created() {
      await Promise.all([
        this.getChannelshealth(),
      ]);
      this.onLoad();
    },
    computed: {
      ...mapGetters('channelshealth', ['channelshealth', 'loaded']),
      noData() {
        return this.channelshealth != null && !this.channelshealth.length;
      },
    },
    methods: {
      ...mapActions('channelshealth', ['getChannelshealth', 'onLoad']),
    },
  };
</script>
