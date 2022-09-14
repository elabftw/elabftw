/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
export class SemverCompare {
  current: Array<number>;
  latest: Array<number>;

  constructor(current: string, latest: string) {
    this.current = this.parse(current);
    this.latest = this.parse(latest);
  }

  /**
   * Returns true if the current version is older than the latest version
   */
  isOld(): boolean
  {
    for (let i = 0;  i < 3; i++) {
      if (this.current[i] < this.latest[i]) {
        return true;
      }
      if (this.current[i] > this.latest[i]) {
        return false;
      }
    }
    return false;
  }

  private parse(version: string): Array<number>
  {
    return version.split('-')[0].split('.').map(n => parseInt(n, 10));
  }
}
