<script lang='ts'>
  import type { PasswordLabels, PasswordOptions } from '../interfaces';
  import PasswordInput from "./PasswordInput.svelte";
  
  type Team = { id: number; name: string; };
  
  type RegisterLabels = PasswordLabels & {
    team: string;
    selectTeam: string;
    email: string;
    password: string;
    firstname: string;
    lastname: string;
    create: string;
    showDomains: string;
    allowedDomains: string;
    privacyPolicy: string;
  };
  
  type RegisterOptions = {
    selectedTeam: string | null;
    teams: Team[];
    csrf: string;
    emailDomain: string;
    hasPrivacyPolicy: boolean;
    password: PasswordOptions;
    labels: RegisterLabels;
  };
  
  let { options } = $props<{ options: RegisterOptions }>();
  
  let showDomains = $state(false);
  
  const passwordLabels: PasswordLabels = {
    length: options.labels.passwordLength,
    letters: options.labels.passwordLetters,
    digit: options.labels.passwordDigit,
    special: options.labels.passwordSpecial,
    met: options.labels.met,
    notMet: options.labels.notMet,
    showPassword: options.labels.showPassword,
  };
</script>

<form method='post' autocomplete='off' action='app/controllers/RegisterController.php'>
  <input type='hidden' name='bot' value='' />
  
  {@html options.csrf}
  
  <div class='form-group mx-auto col-md-4'>
    <div class='row'>
      {@html '<!-- [html-validate-disable-block valid-for, prefer-native-element: suppress errors from tom-select] -->'}
      <label for='team'>{options.labels.team}</label>
      <select
        name='team'
        class='form-control'
        id='team'
        required
        autocomplete='off'
        aria-label={options.labels.team}
      >
        <option value='' selected={!options.selectedTeam} disabled>
          {options.labels.selectTeam}
        </option>
        
        {#each options.teams as team}
          <option
            value={team.id}
            selected={String(team.id) === String(options.selectedTeam)}
          >
            {team.name}
          </option>
        {/each}
      </select>
    </div>
    
    <div class='row mt-2'>
      <label for='email'>{options.labels.email}</label>
      
      <input
        name='email'
        class='form-control'
        type='email'
        id='email'
        autocomplete='email'
        required
      />
      
      {#if options.emailDomain}
        <button
          type='button'
          class='mt-1 btn btn-secondary btn-sm'
          onclick={() => showDomains = !showDomains}
        >
          {options.labels.showDomains}
        </button>
        
        {#if showDomains}
          <p class='smallgray'>
            {options.labels.allowedDomains}
          </p>
        {/if}
      {/if}
    </div>
    
    <div class='row mt-2'>
      <label for='password'>{options.labels.password}</label>
      
      <PasswordInput
        required
        options={options.password}
        labels={passwordLabels}
      />
    </div>
    
    <div class='row mt-2'>
      <label for='firstname'>{options.labels.firstname}</label>
      
      <input
        name='firstname'
        class='form-control'
        type='text'
        id='firstname'
        autocomplete='given-name'
        required
      />
    </div>
    
    <div class='row mt-2'>
      <label for='lastname'>{options.labels.lastname}</label>
      
      <input
        name='lastname'
        class='form-control'
        type='text'
        id='lastname'
        autocomplete='family-name'
        required
      />
    </div>
    
    {#if options.hasPrivacyPolicy}
      <div class='row mt-2'>
        <div class='form-group form-check'>
          <input
            name='privacy-policy'
            class='form-check-input'
            type='checkbox'
            id='privacy-policy'
            required
          />
          
          <label class='form-check-label' for='privacy-policy'>
            {options.labels.privacyPolicy}
            <button
              type='button'
              class='btn btn-link p-0'
              data-action='toggle-modal'
              data-target='policyModal'
            >
              Privacy Policy
            </button>
          </label>
        </div>
      </div>
    {/if}
    
    <div class='mt-4 text-center'>
      <button type='submit' name='Submit' class='btn btn-primary'>
        {options.labels.create}
      </button>
    </div>
  </div>
</form>
