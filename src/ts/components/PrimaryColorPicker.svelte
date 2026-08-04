<script lang="ts">
  import { ApiC } from '../api';
  import { Model } from '../interfaces';
  import { notify } from '../notify';
  import i18next from "../i18n";
  import { getContrastResult } from '../accessibility';
  
  export let initialPrimaryColor = '#813d9c';
  export let initialPrimaryForeground = '#ffffff';
  
  let primaryColor = initialPrimaryColor;
  let primaryForeground = initialPrimaryForeground;
  let isSaving = false;
  const t = i18next.t.bind(i18next);
  
  const hexColorPattern = /^#[0-9a-fA-F]{6}$/;
  
  const applyPreview = (): void => {
    document.documentElement.style.setProperty('--primary', primaryColor);
    document.documentElement.style.setProperty('--primary-fg', primaryForeground);
  };
  
  const saveColors = async (): Promise<void> => {
    if (!hexColorPattern.test(primaryColor)
      || !hexColorPattern.test(primaryForeground)
    ) {
      notify.error('invalid-info');
      return;
    }
    isSaving = true;
    try {
      await ApiC.patch(`${Model.User}/me`, {primary_color: primaryColor, primary_foreground: primaryForeground });
      applyPreview();
    } catch (error) {
      notify.error(error);
    } finally {
      isSaving = false;
    }
  };
  
  const resetColors = async (): Promise<void> => {
    isSaving = true;
    try {
      await ApiC.patch(`${Model.User}/me`, {primary_color: null, primary_foreground: null});
      // remove the user overrides from <html>
      document.documentElement.style.removeProperty('--primary');
      document.documentElement.style.removeProperty('--primary-fg');
      
      // read the defaults now provided by the active theme
      const styles = getComputedStyle(document.documentElement);
      primaryColor = styles.getPropertyValue('--primary').trim();
      primaryForeground = styles
        .getPropertyValue('--primary-fg')
        .trim()
        .replace(/^#([0-9a-fA-F])([0-9a-fA-F])([0-9a-fA-F])$/, '#$1$1$2$2$3$3');
      
      // these values become the new unsaved/reset baseline
      initialPrimaryColor = primaryColor;
      initialPrimaryForeground = primaryForeground;
    } catch (error) {
      notify.error('cannot-reset-settings');
    } finally {
      isSaving = false;
    }
  };
  
  $: contrast = getContrastResult(
    primaryForeground,
    primaryColor,
  );
</script>

<div>
  <div class='d-flex justify-content-between align-items-center mb-3'>
    <label for='primaryColor' class='col-form-label'>{t('primary-color')}</label>
    
    <div class='d-flex align-items-center'>
      <input
        id='primaryColor'
        class='color-input mr-2'
        type='color'
        bind:value={primaryColor}
      >
      <!-- hexadecimal value -->
      <input
        class='form-control'
        type='text'
        bind:value={primaryColor}
        pattern='#[0-9a-fA-F]{6}'
        maxlength='7'
        aria-label={`Hexadecimal ${t('primary-color')}`}
      >
    </div>
  </div>
  
  <hr>
  
  <div class='d-flex justify-content-between align-items-center mb-3'>
    <label for='primaryForeground' class='col-form-label'>
      {t('text-color-primary')}
    </label>
    
    <div class='d-flex align-items-center'>
      <input
        id='primaryForeground'
        class='color-input mr-2'
        type='color'
        bind:value={primaryForeground}
      >
      <input
        class='form-control'
        type='text'
        bind:value={primaryForeground}
        pattern='#[0-9a-fA-F]{6}'
        maxlength='7'
        aria-label={`Hexadecimal ${t('text-color-primary')}`}
      >
    </div>
  </div>
  <hr>
  
  <div class='box' style={`--primary: ${primaryColor}; --primary-fg: ${primaryForeground};`}>
    <p class='mb-2'>{t('preview')}</p>
    
    <button type='button' class='btn btn-primary mr-2'>
      <i class='fas fa-pencil mr-1'></i>
      <span>{t('primary-color')}</span>
    </button>
    
    <p class={`small mt-2 ${contrast.className}`}>
      <strong>{contrast.icon} {contrast.level}</strong>
      ({contrast.description})
      • {contrast.ratio.toFixed(1)}:1
    </p>
  </div>
  
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
