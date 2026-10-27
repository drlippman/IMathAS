<template>
  <div class="claim-overlay" role="alertdialog" aria-modal="true"
    aria-labelledby="claim_hdr" aria-describedby="claim_body"
  >
    <div class="claim-box">
      <h2 id="claim_hdr">{{ lost ? $t('claim-lost-title') : $t('claim-offline-title') }}</h2>
      <p id="claim_body">{{ lost ? $t('claim-lost-body') : $t('claim-offline-body') }}</p>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ClaimOverlay',
  props: ['lost']
};
</script>

<style>
html.claim-blocked-scroll {
  overflow: hidden;
}
#app.claim-blocked > *:not(.claim-overlay) {
  visibility: hidden;
}
.claim-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 10000;
  display: flex;
  align-items: center;
  justify-content: center;
  background: Canvas;
  color: CanvasText;
}
.claim-box {
  max-width: 30em;
  padding: 1em;
}
</style>
