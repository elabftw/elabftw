<script lang="ts">
  /**
   * @author Nicolas CARPi / Deltablot
   * @author Moustapha Camara / Deltablot
   * @copyright 2026 Nicolas CARPi
   * @see https://www.elabftw.net Official website
   * @license AGPL-3.0
   * @package elabftw
   */
  import { ApiC } from '../api';
  import { Model } from '../interfaces';
  import i18next from "../i18n";
  import { getContrastResult } from '../accessibility';

  const styles = getComputedStyle(document.documentElement);
  let primaryBg = styles.getPropertyValue('--primary');
  let primaryFg = styles.getPropertyValue('--primary-fg')
  let isSaving = false;
  const t = i18next.t.bind(i18next);

  const applyPreview = (): void => {
    document.documentElement.style.setProperty('--primary', primaryBg);
    document.documentElement.style.setProperty('--primary-fg', primaryFg);
  };

  const saveColors = async (): Promise<void> => {
    isSaving = true;
    try {
      await ApiC.patch(`${Model.User}/me`, {primary_bg: primaryBg, primary_fg: primaryFg});
      applyPreview();
    } finally {
      isSaving = false;
    }
  };

  const resetColors = async (): Promise<void> => {
    await ApiC.patch(`${Model.User}/me`, {primary_bg: null, primary_fg: null});
    // remove the user overrides from <html>
    document.documentElement.style.removeProperty('--primary');
    document.documentElement.style.removeProperty('--primary-fg');
    // fetch the colors from current theme
    const styles = getComputedStyle(document.documentElement);
    primaryBg = styles.getPropertyValue('--primary');
    primaryFg = styles.getPropertyValue('--primary-fg');
  };

  $: contrast = getContrastResult(
    primaryBg,
    primaryFg,
  );
</script>

<div>
  <div class='d-flex justify-content-between align-items-center mb-3'>
    <label for='primaryBgInput' class='col-form-label'>{t('background-color')}</label>
    <input
      id='primaryColorInput'
      class='color-input mr-2'
      type='color'
      bind:value={primaryBg}
      on:change={applyPreview}
    >
  </div>
  <hr>
  <div class='d-flex justify-content-between align-items-center mb-3'>
    <label for='primaryFgInput' class='col-form-label'>
      {t('text-color')}
    </label>
    <input
      id='primaryForegroundInput'
      class='color-input mr-2'
      type='color'
      bind:value={primaryFg}
      on:change={applyPreview}
    >
  </div>
  <hr>
  <p class='mb-2'>{t('accessibility')}</p>
  <p class={`small mt-2 ${contrast.className}`}>
    {contrast.icon} {contrast.level} ({contrast.description}) • {contrast.ratio.toFixed(1)}:1
  </p>

  <hr>
  <div class='mt-3'>
    <button
      type='button'
      class='btn btn-primary mr-2'
      disabled={isSaving}
      on:click={saveColors}
    >
      {isSaving ? t('please-wait') : t('save')}
    </button>
    <button
      type='button'
      class='btn btn-ghost'
      disabled={isSaving}
      on:click={resetColors}
    >
      {t('reset')}
    </button>
  </div>
</div>
