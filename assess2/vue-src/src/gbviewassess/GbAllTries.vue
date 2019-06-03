<template>
  <div>
    <p><strong>{{ $t('gradebook.all_tries') }}</strong></p>
    <div
      v-for="(part,index) in tries"
      :key = "index"
      class="med-below med-left"
    >
      <div v-if="tries.length > 1">
        <strong>{{ $t('gradebook.part_n', {n: index+1}) }}</strong>
      </div>
      <div v-for="(trystr, tryn) in part" :key="tryn">
        {{ $t('gradebook.try_n', {n: tryn+1}) }}: {{ trystr }}
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'GbAllTries',
  props: ['tries']
};
</script>
