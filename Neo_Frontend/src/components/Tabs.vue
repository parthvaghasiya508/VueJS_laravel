<template>
  <fragment>
    <div class="panel-tabs">
      <button
        class="btn-icon search-icon d-md-none"
        @click="$parent.toggleSearchBox()">
        <!--        @click="searchShown = (searchShown) ? false : true">-->
        <icon width="20" height="20" type="search"/>
      </button>
      <div class="tabs">
        <div v-for="{ id, title } in items" class="tab" :key="`tab-${id}`"
             :class="{ active: id === value }" @click="switchTab(id)">
          {{ title }}
        </div>
        <div class="tab"> CommunicationCommunicationCommunication</div>
      </div>
    </div>
    <div v-if="withContent"
         class="panel panel-content position-relative tabs-content"
         :class="{'hide-overlay': hideOverlay}">
      <slot v-if="value != null" :name="`tab(${value})`"/>
    </div>
  </fragment>
</template>

<script>
  export default {
    name: 'Tabs',
    props: {
      items: {
        required: true,
        validator: (v) => v == null || Array.isArray(v),
      },
      value: {
        required: true,
      },
      withContent: {
        type: Boolean,
        default: false,
      },
      hideOverlay: {
        type: Boolean,
        default: false,
      },
    },
    created() {
      if (this.value == null && this.items != null && this.items[0] != null) {
        this.$emit('input', this.items[0].id);
      }
    },
    methods: {
      switchTab(id) {
        if (this.value !== id) {
          this.$emit('input', id);
          this.$emit('switch', id);
        }
      },
    },
  };
</script>
