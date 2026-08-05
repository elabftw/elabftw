<script lang='ts'>
  // Labels displayed next to each password requirement
  type Labels = {
    length: string;
    letters: string;
    digit: string;
    special: string;
    met: string;
    notMet: string;
    showPassword: string;
  };
  
  // config passed from Twig
  let { minLength,  complexity,  pattern,  title,  labels } = $props<{
    minLength: number;
    complexity: number;
    pattern: string;
    title: string;
    labels: Labels;
  }>();
  
  // current password typed by the user
  let password = $state('');
  // Rebuild the checklist whenever the password changes
  const rules = $derived.by(() => {
    const rules = [
      {
        // min password length
        label: labels.length,
        met: Array.from(password).length >= minLength,
      },
    ];
    // for the weak policy: uppercase + lowercase letters
    if (complexity >= 10) {
      rules.push({
        label: labels.letters,
        met: (/\p{Ll}/u.test(password) && /\p{Lu}/u.test(password))
          || /\p{Lo}/u.test(password),
      });
    }
    // medium policy: at least one digit
    if (complexity >= 20) {
      rules.push({
        label: labels.digit,
        met: /\d/u.test(password),
      });
    }
    // strong policy: at least one special character
    if (complexity >= 30) {
      rules.push({
        label: labels.special,
        met: /[\p{P}\p{S}]/u.test(password),
      });
    }
    
    return rules;
  });
</script>

<input bind:value={password} name='password' type='password'
       class='form-control' id='password' minlength={minLength}
       {pattern} {title} autocomplete='new-password'
       aria-describedby='password-requirements' required />

<div class='input-group-append'>
  <button type='button' class='btn btn-ghost' data-action='toggle-password'
          title={labels.showPassword} aria-label={labels.showPassword}><i
    class='fas fa-eye' aria-hidden='true'></i></button>
</div>

<ul id='password-requirements' class='small text-left mt-2 mb-0 pl-3 w-100'>
  {#each rules as rule}
    <li class:text-success={rule.met} class:text-muted={!rule.met}>
      {rule.label} ({rule.met ? labels.met : labels.notMet})
    </li>
  {/each}
</ul>
