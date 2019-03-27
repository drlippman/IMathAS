<template>
  <div>
    <label for="password">
      {{ $t('password.label') }}
    </label>
    <input
      :value="value"
      type="password"
      autocomplete="off"
      @input="handleInput"
    >
  </div>
</template>

<script>
export default {
  name: 'PasswordEntry',
  props: ['value'],
  methods: {
    handleInput (event) {
      this.$emit('input', event.target.value);
    }
  }
};
</script>
