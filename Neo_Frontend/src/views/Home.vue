<template>
  <div class="page-dashboard">
    <div class="panel">

      <div class="panel-title position-relative w-100 title mb-2">
        <p class="mb-2">Dashboard</p>
      </div>
      <div class="bg-light mt-4">
        <button class="btn btn-secondary btn-lg"
                :class="{ 'btn-loading': authenticating }"
                @click.prevent="logout"
        >
          Log out
        </button>
      </div>
    </div>
  </div>
</template>

<script>
  import { mapGetters, mapActions } from 'vuex';

  export default {
    name: 'Home',
    computed: {
      ...mapGetters('auth', ['authenticating']),
    },
    methods: {
      ...mapActions('auth', ['logout']),
    },
  };
</script>
