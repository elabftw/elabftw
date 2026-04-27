<script lang='ts'>
  /**
   * @author Nicolas CARPi / Deltablot
   * @copyright 2026 Nicolas CARPi
   * @see https://www.elabftw.net Official website
   * @license AGPL-3.0
   * @package elabftw
   */
  import { onMount } from 'svelte';
  import Prism from 'prismjs';
  import { writable, type Writable } from 'svelte/store';
  import '../prism-elabftwquery';
  import i18next from '../i18n';

  type Props = {
    searchQuery?: Writable<string>;
    buttonLabel?: string;
  };

  const t = i18next.t.bind(i18next);

  const {
    searchQuery = writable(''),
    buttonLabel = t('search'),
  }: Props = $props();

  let inputEl: HTMLInputElement;
  let overlayEl: HTMLDivElement;

  const currentQuery = $derived($searchQuery ?? '');

  const highlighted = $derived(
    Prism.highlight(
      currentQuery.length > 0 ? currentQuery : ' ',
      Prism.languages.elabftwquery,
      'elabftwquery',
    ),
  );

  const syncScroll = (): void => {
    if (!inputEl || !overlayEl) {
      return;
    }
    overlayEl.scrollLeft = inputEl.scrollLeft;
  };

  const handleInput = (): void => {
    searchQuery.set(inputEl.value);
    syncScroll();
  };

  onMount(() => {
    syncScroll();

    const handleExternalSet = (event: Event): void => {
      const customEvent = event as CustomEvent<string>;
      searchQuery.set(customEvent.detail ?? '');

      queueMicrotask(() => {
        if (inputEl) {
          inputEl.value = currentQuery;
          syncScroll();
        }
      });
    };

    document.addEventListener('search-query:set', handleExternalSet as EventListener);

    return () => {
      document.removeEventListener('search-query:set', handleExternalSet as EventListener);
    };
  });
</script>

<div class='input-group w-100 search-bar-input-group'>
  <div class='search-highlight-input'>
    <div
      class='highlight-layer'
      class:is-empty={!currentQuery}
      bind:this={overlayEl}
      aria-hidden='true'
    >
      <code>{@html highlighted}</code>
    </div>

    <input
      bind:this={inputEl}
      id='extendedArea'
      name='q'
      type='text'
      class='form-control syntax-input'
      placeholder={t('search')}
      aria-label={t('search')}
      spellcheck='false'
      autocomplete='off'
      autocapitalize='off'
      autocorrect='off'
      value={currentQuery}
      oninput={handleInput}
      onscroll={syncScroll}
    />
  </div>

  <div class='input-group-append'>
    <button
      class='btn btn-secondary'
      type='submit'
      aria-label={buttonLabel}
      title={buttonLabel}
    >
      <i class='fas fa-magnifying-glass'></i>
    </button>
  </div>
</div>
