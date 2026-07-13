export enum AppTheme {
  Light = 'light',
  Dark = 'dark',
  DarkBlue = 'dark-blue',
}

export const getAppTheme = (): AppTheme => {
  const classes = document.documentElement.classList;
  if (classes.contains('dark-blue-mode')) {
    return AppTheme.DarkBlue;
  }
  if (classes.contains('dark-mode')) {
    return AppTheme.Dark;
  }
  return AppTheme.Light;
};

export const isDarkTheme = (): boolean => {
  return getAppTheme() !== AppTheme.Light;
};

export const getAgGridTheme = (): string => {
  switch (getAppTheme()) {
    case AppTheme.DarkBlue:
      return 'ag-theme-quartz-dark';
    case AppTheme.Dark:
      return 'ag-theme-alpine-dark';
    default:
      return 'ag-theme-alpine';
  }
};
