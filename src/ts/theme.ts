export enum ThemeVariant {
  Auto = 0,
  Light = 1,
  Dark = 2,
  Midnight = 3,
}

export enum AppTheme {
  Light = 'light',
  Dark = 'dark',
  Midnight = 'midnight',
}

const themeClasses = ['dark-mode', 'midnight'] as const;

export const isThemeVariant = (value: number): value is ThemeVariant => {
  return Object.values(ThemeVariant).includes(value);
};

export const applyTheme = (themeVariant: ThemeVariant): void => {
  document.documentElement.classList.remove(...themeClasses);
  switch (themeVariant) {
  case ThemeVariant.Dark:
    document.documentElement.classList.add('dark-mode');
    break;
  case ThemeVariant.Midnight:
    document.documentElement.classList.add('midnight');
    break;
  case ThemeVariant.Auto:
  case ThemeVariant.Light:
    break;
  }
};

export const updateThemeControls = (themeVariant: ThemeVariant): void => {
  document.querySelectorAll<HTMLElement>('[data-current-theme]')
    .forEach(control => {
      control.dataset.currentTheme = String(themeVariant);
    });
};

export const getAppTheme = (): AppTheme => {
  const classes = document.documentElement.classList;
  if (classes.contains('midnight')) {
    return AppTheme.Midnight;
  }
  if (classes.contains('dark-mode')) {
    return AppTheme.Dark;
  }
  return AppTheme.Light;
};

// for components like tinymce that have either light / dark only
export const isDarkTheme = (): boolean => {
  return getAppTheme() !== AppTheme.Light;
};

export const getAgGridTheme = (): string => {
  switch (getAppTheme()) {
  case AppTheme.Midnight:
    return 'ag-theme-quartz-dark';
  case AppTheme.Dark:
    return 'ag-theme-alpine-dark';
  case AppTheme.Light:
    return 'ag-theme-alpine';
  }
};
