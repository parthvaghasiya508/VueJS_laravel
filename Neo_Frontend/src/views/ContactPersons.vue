<template>
  <div class="page-contact-persons">
    <div class="panel-title position-relative w-100 title">
      <p>{{ $t('pages.contactpersons.title') }}</p>
    </div>
    <contact-persons/>
  </div>
</template>

<script>
  import ContactPersons from '@/components/Pages/ContactPersons.vue';

  export default {
    name: 'PageContactPersons',
    components: { ContactPersons },
  };
</script>
