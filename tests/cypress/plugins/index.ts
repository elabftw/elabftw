/// <reference types="cypress" />
// ***********************************************************
// This example plugins/index.js can be used to load plugins
//
// You can change the location of this file or turn off loading
// the plugins file with the 'pluginsFile' configuration option.
//
// You can read more here:
// https://on.cypress.io/plugins-guide
// ***********************************************************

// This function is called when a project is opened or re-opened (e.g. due to
// the project's config changing)

import { readFileSync } from 'node:fs';
import { inflateRawSync } from 'node:zlib';

// minimal zip reader: locate one entry in a .zip/.xlsx archive and return its content as text
const readZipEntry = (archivePath: string, entryName: string): string | null => {
  const buf = readFileSync(archivePath);
  // the End Of Central Directory record sits within the last 64KiB of the file
  let eocd = -1;
  for (let i = buf.length - 22; i >= Math.max(0, buf.length - 65558); i--) {
    if (buf.readUInt32LE(i) === 0x06054b50) { eocd = i; break; }
  }
  if (eocd === -1) return null;
  const entryCount = buf.readUInt16LE(eocd + 10);
  let offset = buf.readUInt32LE(eocd + 16);
  for (let i = 0; i < entryCount; i++) {
    if (buf.readUInt32LE(offset) !== 0x02014b50) return null;
    const method = buf.readUInt16LE(offset + 10);
    const compressedSize = buf.readUInt32LE(offset + 20);
    const nameLen = buf.readUInt16LE(offset + 28);
    const extraLen = buf.readUInt16LE(offset + 30);
    const commentLen = buf.readUInt16LE(offset + 32);
    const localHeaderOffset = buf.readUInt32LE(offset + 42);
    const name = buf.toString('utf8', offset + 46, offset + 46 + nameLen);
    if (name === entryName) {
      const dataStart = localHeaderOffset + 30
        + buf.readUInt16LE(localHeaderOffset + 26)
        + buf.readUInt16LE(localHeaderOffset + 28);
      const data = buf.subarray(dataStart, dataStart + compressedSize);
      return (method === 8 ? inflateRawSync(data) : data).toString('utf8');
    }
    offset += 46 + nameLen + extraLen + commentLen;
  }
  return null;
};

/**
 * @type {Cypress.PluginConfig}
 */
module.exports = (on: Cypress.PluginEvents) => {
  // `on` is used to hook into various events Cypress emits
  // `config` is the resolved Cypress config
  on('task', {
    readZipEntry({ path, entry }: { path: string; entry: string }) {
      return readZipEntry(path, entry);
    },
  });
  on('before:browser:launch', (browser, launchOptions) => {
    console.log(
      'Launching browser %s. Is headless: %s',
      browser.name,
      browser.isHeadless,
    );

    // the browser width and height we want to get
    // our screenshots and videos will be of that resolution
    const width = 1920;
    const height = 1080;

    console.log('Setting the browser window size to %d x %d', width, height);

    if (browser.name === 'chrome' && browser.isHeadless) {
      launchOptions.args.push(`--window-size=${width},${height}`);

      // force screen to be non-retina and just use our given resolution
      launchOptions.args.push('--force-device-scale-factor=1');
    }

    if (browser.name === 'electron' && browser.isHeadless) {
      // might not work on CI for some reason
      launchOptions.preferences.width = width;
      launchOptions.preferences.height = height;
    }

    if (browser.name === 'firefox' && browser.isHeadless) {
      launchOptions.args.push(`--width=${width}`);
      launchOptions.args.push(`--height=${height}`);
    }

    // IMPORTANT: return the updated browser launch options
    return launchOptions;
  });
};
