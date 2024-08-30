/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
export default class Tab {
  currentTab: number;

  constructor() {
    this.currentTab = this.getTabFromUrl();
  }

  // display the current tab and add an event listen so we can switch tabs
  init(menu: HTMLElement): void {
    this.display(this.currentTab);
    document.getElementById('loading-spinner').remove();
    // add a listener on actionable elements from the menu
    menu.querySelectorAll('[data-action="switch-tab"]').forEach(el => {
      el.addEventListener('click', () => {
        const tabid = parseInt((el as HTMLElement).dataset.tabtarget, 10);
        this.switchTo(tabid);
      });
    });
  }

  // get the current tab=X parameter value from the url
  getTabFromUrl(): number {
    const params = new URLSearchParams(document.location.search.slice(1));
    const tabid = parseInt(params.get('tab'), 10);
    // default tab is 1
    return Number.isSafeInteger(tabid) ? tabid : 1;
  }

  // display a tab content and adjust the menu
  display(tabid: number): void {
    // hide current div
    document.querySelector(`[data-tabcontent="${this.currentTab}"]`).toggleAttribute('hidden', true);
    // and show the div we want
    document.querySelector(`[data-tabcontent="${tabid}"]`).toggleAttribute('hidden');
    // make current tabhandle unselected
    document.querySelector(`[data-tabtarget="${this.currentTab}"]`).classList.remove('selected');
    // and select the one we want
    document.querySelector(`[data-tabtarget="${tabid}"]`).classList.add('selected');
    // show the tab change in the url
    const params = new URLSearchParams(document.location.search);
    const hash = document.location.hash;
    params.set('tab', String(tabid));
    history.replaceState(null, '', `?${params.toString()}${hash}`);
    // remember where we are
    this.currentTab = tabid;
  }

  switchTo(tabid: number): void {
    // do nothing if we try to switch to the current tab
    if (tabid === this.currentTab) {
      return;
    }
    this.display(tabid);
  }
}
