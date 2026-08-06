<script lang='ts'>
  /**
   * @author Nicolas CARPi / Deltablot
   * @author Moustapha Camara / Deltablot
   * @copyright 2026 Nicolas CARPi
   * @see https://www.elabftw.net Official website
   * @license AGPL-3.0
   * @package elabftw
   */
  import type { PasswordLabels, PasswordOptions } from '../interfaces';
  let { name = 'password', id = 'password', required = false, options, labels } = $props<{
    name?: string;
    id?: string;
    required?: boolean;
    options: PasswordOptions;
    labels: PasswordLabels;
  }>();

  // current password typed by the user
  let password = $state('');
  let visible = $state(false);

  // build the requirements enabled by the configured policy
  const rules = $derived.by(() => {
    const rules = [{
      // min password length
      label: labels.length,
      met: Array.from(password).length >= options.minLength,
    }];
    // for the weak policy: uppercase + lowercase letters
    if (options.complexity >= 10) {
      rules.push({
        label: labels.letters,
        met: (/\p{Ll}/u.test(password) && /\p{Lu}/u.test(password))
          || /\p{Lo}/u.test(password),
      });
    }
    // medium policy: at least one digit
    if (options.complexity >= 20) {
      rules.push({
        label: labels.digit,
        met: /\d/u.test(password),
      });
    }
    // strong policy: at least one special character
    if (options.complexity >= 30) {
      rules.push({
        label: labels.special,
        met: /[\p{P}\p{S}]/u.test(password),
      });
    }
    return rules;
  });
</script>

<div class='input-group'>
  <input
    bind:value={password}
    {name}
    {id}
    type={visible ? 'text' : 'password'}
    class='form-control'
    minlength={options.minLength}
    pattern={options.pattern}
    title={options.title}
    autocomplete='new-password'
    aria-describedby={`${id}-requirements`}
    {required}
  />
  <div class='input-group-append'>
    <button type='button' class='btn btn-ghost' title={labels.showPassword} aria-label={labels.showPassword} onclick={() => visible = !visible}>
      <i class:fa-eye={!visible} class:fa-eye-slash={visible} class='fas' aria-hidden='true'></i>
    </button>
  </div>
</div>

<ul id={`${id}-requirements`} class='small text-left mt-2 mb-0 pl-3'>
  {#each rules as rule}
    <li class:text-success={rule.met} class:text-muted={!rule.met}>
      {rule.met ? labels.met : labels.notMet} {rule.label}
    </li>
  {/each}
</ul>
