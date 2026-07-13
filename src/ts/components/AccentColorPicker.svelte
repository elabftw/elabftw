<script lang="ts">
  import { ApiC } from '../api';
  import { Model } from '../interfaces';
  import { notify } from '../notify';
  
  export let initialAccentColor = '#813d9c';
  export let initialAccentForeground = '#ffffff';
  
  let accentColor = initialAccentColor;
  let accentForeground = initialAccentForeground;
  let isSaving = false;
  
  const hexColorPattern = /^#[0-9a-fA-F]{6}$/;
  
  const applyPreview = (): void => {
    document.documentElement.style.setProperty('--primary', accentColor);
    document.documentElement.style.setProperty('--primary-fg', accentForeground);
  };
  
  const saveColors = async (): Promise<void> => {
    if (!hexColorPattern.test(accentColor)
      || !hexColorPattern.test(accentForeground)
    ) {
      notify.error('Invalid color value.');
      return;
    }
    isSaving = true;
    try {
      await ApiC.patch(`${Model.User}/me`, {accent_color: accentColor, accent_foreground: accentForeground });
      applyPreview();
    } catch (error) {
      notify.error('Could not save the colors.');
    } finally {
      isSaving = false;
    }
  };
  
  const resetColors = async (): Promise<void> => {
    isSaving = true;
    try {
      await ApiC.patch(`${Model.User}/me`, {accent_color: null, accent_foreground: null});
      // remove the user overrides from <html>
      document.documentElement.style.removeProperty('--primary');
      document.documentElement.style.removeProperty('--primary-fg');
      
      // read the defaults now provided by the active theme
      const styles = getComputedStyle(document.documentElement);
      accentColor = styles.getPropertyValue('--primary').trim();
      accentForeground = styles
        .getPropertyValue('--primary-fg')
        .trim()
        .replace(/^#([0-9a-fA-F])([0-9a-fA-F])([0-9a-fA-F])$/, '#$1$1$2$2$3$3');
      
      // these values become the new unsaved/reset baseline
      initialAccentColor = accentColor;
      initialAccentForeground = accentForeground;
    } catch (error) {
      notify.error('Could not reset the colors.');
    } finally {
      isSaving = false;
    }
  };
</script>

<div class='accent-color-settings'>
  <div class='d-flex justify-content-between align-items-center mb-3'>
<!--    TODO I18n -->
    <label for='accentColor' class='col-form-label'>Primary color</label>
    
    <div class='d-flex align-items-center'>
      <input
        id='accentColor'
        class='color-input mr-2'
        type='color'
        bind:value={accentColor}
        aria-label='Primary color'
      >
      <input
        class='form-control accent-hex-input'
        type='text'
        bind:value={accentColor}
        pattern='#[0-9a-fA-F]{6}'
        maxlength='7'
        aria-label='Primary color hexadecimal value'
      >
    </div>
  </div>
  
  <hr>
  
  <div class='d-flex justify-content-between align-items-center mb-3'>
    <label for='accentForeground' class='col-form-label'>
      <!--    TODO I18n -->
      Text color on primary buttons
    </label>
    
    <div class='d-flex align-items-center'>
      <input
        id='accentForeground'
        class='color-input mr-2'
        type='color'
        bind:value={accentForeground}
        aria-label='Primary foreground color'
      >
      
      <input
        class='form-control accent-hex-input'
        type='text'
        bind:value={accentForeground}
        pattern='#[0-9a-fA-F]{6}'
        maxlength='7'
        aria-label='Primary foreground hexadecimal value'
      >
    </div>
  </div>
  
  <hr>
  
  <div
    class='theme-accent-preview box'
    style={`--primary: ${accentColor}; --primary-fg: ${accentForeground};`}
  >
    <p class='mb-2'>
      Preview
    </p>
    
    <button type='button' class='btn btn-primary mr-2'>
      <i class='fas fa-pencil mr-1'></i>
      Primary button
    </button>
  </div>
  
  <div class='mt-3'>
    <button
      type='button'
      class='btn btn-primary mr-2'
      disabled={isSaving}
      on:click={saveColors}
    >
      <!--    TODO I18n -->
      {isSaving ? 'Saving...' : 'Save'}
    </button>
    
    <button
      type='button'
      class='btn btn-ghost'
      disabled={isSaving}
      on:click={resetColors}
    >
      Reset
    </button>
  </div>
</div>
