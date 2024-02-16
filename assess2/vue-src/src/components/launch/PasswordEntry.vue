<template>
  <div>
    <label for="password">
      {{ $t('password.label') }}
    </label>
    <input
      :value="modelValue"
      id="password"
      name="password"
      type="password"
      autocomplete="off"
      @input="handleInput"
      @keydown.enter="handleEnter"
    >
  </div>
</template>

<script>
export default {
  name: 'PasswordEntry',
  props: ['modelValue'],
  methods: {
    handleInput (event) {
      this.$emit('update:modelValue', event.target.value);
    },
    handleEnter (event) {
      event.preventDefault();
      event.stopPropagation();
      this.$emit('onenter');
    }
  }
};
</script>
