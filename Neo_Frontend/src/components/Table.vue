<template>
  <div class="widget table-wrapper">
    <p class="table-title">{{title}}</p>
    <b-table
      hover
      :items="items"
      :fields="fields"
      show-empty
      responsive
      :empty-text="$t('pages.dashboard.booking-table.labels.no-data')">

    </b-table>
  </div>
</template>

<script>
  export default {
    name: 'Table',
    props: {
      title: {
        type: String,
        default: '',
        required: true,
      },
      items: {
        type: Array,
        required: true,
      },
      fields: {
        type: Array,
        required: true,
      },
    },
  };
</script>
