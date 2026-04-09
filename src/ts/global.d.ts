declare global {
  interface JQuery {
    autocomplete(...args: any[]): any;
    dropdown(...args: any[]): any;
    fancybox(...args: any[]): any;
    modal(...args: any[]): any;
    sortable(...args: any[]): any;
  }
}

export {};
