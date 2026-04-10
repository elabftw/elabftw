declare global {
  interface JQuery {
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    autocomplete(options: any): this;
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    dropdown(method: string, ...args: any[]): this;
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    fancybox(options?: any): this;
    modal(method?: string): this;
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    sortable(options: any): this;
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    sortable(method: 'toArray', options?: any): string[];
  }
}

export {};
