declare global {
  interface JQuery {
    autocomplete(options: any): this;
    dropdown(method: string, ...args: any[]): this;
    fancybox(options?: any): this;
    modal(method?: string): this;
    sortable(options: any): this;
    sortable(method: 'toArray', options?: any): string[];
  }
}

export {};
