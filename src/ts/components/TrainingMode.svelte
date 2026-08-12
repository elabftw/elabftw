<script lang='ts'>
  /**
   * @author Nicolas CARPi / Deltablot
   * @copyright 2026 Nicolas CARPi
   * @see https://www.elabftw.net Official website
   * @license AGPL-3.0
   * @package elabftw
   */
  import { onMount } from 'svelte';

  let enabled = $state(false);
  let visible = $state(false);
  let x = $state(0);
  let y = $state(0);
  let clickId = $state(0);

  function syncTrainingMode(): void {
    enabled = localStorage.getItem('trainingMode') === '1';
    document.documentElement.classList.toggle('training-mode', enabled);

    if (!enabled) {
      visible = false;
    }
  }

  onMount(() => {
    const handlePointerMove = (event: PointerEvent): void => {
      if (!enabled) {
        return;
      }

      x = event.clientX;
      y = event.clientY;
      visible = true;
    };

    const handlePointerDown = (): void => {
      if (!enabled) {
        return;
      }

      clickId += 1;
    };

    const handlePointerLeave = (): void => {
      visible = false;
    };

    syncTrainingMode();

    window.addEventListener('training-mode-change', syncTrainingMode);
    window.addEventListener('storage', syncTrainingMode);
    window.addEventListener('pointermove', handlePointerMove, { passive: true });
    window.addEventListener('pointerdown', handlePointerDown, { passive: true });
    window.addEventListener('blur', handlePointerLeave);

    return () => {
      window.removeEventListener('training-mode-change', syncTrainingMode);
      window.removeEventListener('storage', syncTrainingMode);
      window.removeEventListener('pointermove', handlePointerMove);
      window.removeEventListener('pointerdown', handlePointerDown);
      window.removeEventListener('blur', handlePointerLeave);
      document.documentElement.classList.remove('training-mode');
    };
  });
</script>

{#if enabled && visible}
  <div
    class='training-pointer'
    style={`left: ${x}px; top: ${y}px;`}
    aria-hidden='true'
  >
    {#key clickId}
      <div
        class='training-halo'
        class:clicked={clickId > 0}
      ></div>
    {/key}
  </div>
{/if}
