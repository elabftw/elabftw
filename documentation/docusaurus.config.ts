import {themes as prismThemes} from 'prism-react-renderer';
import type {Config} from '@docusaurus/types';
import type * as Preset from '@docusaurus/preset-classic';

// This runs in Node.js - Don't use client-side code here (browser APIs, JSX...)

const config: Config = {
  title: 'eLabFTW Documentation',
  tagline: 'Official documentation',
  favicon: 'img/favicon.ico',
  plugins: [
    [
      "@cmfcmf/docusaurus-search-local",
      {
        indexBlog: false,
      },
    ],
  ],

  // Future flags, see https://docusaurus.io/docs/api/docusaurus-config#future
  future: {
    v4: true, // Improve compatibility with the upcoming Docusaurus v4
  },

  // Set the production url of your site here
  url: 'https://doc.elabftw.net',
  // Set the /<baseUrl>/ pathname under which your site is served
  // For GitHub pages deployment, it is often '/<projectName>/'
  //baseUrl: '/',
  baseUrl: '/',

  onBrokenLinks: 'throw',

  // Even if you don't use internationalization, you can use this field to set
  // useful metadata like html lang. For example, if your site is Chinese, you
  // may want to replace "en" with "zh-Hans".
  i18n: {
    defaultLocale: 'en',
    locales: ['en'],
  },

  presets: [
    [
      'classic',
      {
        docs: {
          sidebarPath: './sidebars.ts',
          editUrl:
            'https://github.com/elabftw/documentation/tree/master/',
        },
        blog: false,
        theme: {
          customCss: './src/css/custom.css',
        },
      } satisfies Preset.Options,
    ],
  ],

  themeConfig: {
    image: 'img/elabftw-logo.png',
    colorMode: {
      respectPrefersColorScheme: true,
    },
    navbar: {
      title: 'Home',
      logo: {
        alt: 'elabftw logo',
        src: 'img/elabftw-logo-only.svg',
      },
      items: [
         {
        type: "docSidebar",
        sidebarId: "installSidebar",
        label: "Installation",
        position: "left",
      },
      {
        type: "docSidebar",
        sidebarId: "usageSidebar",
        label: "Usage",
        position: "left",
      },
      {
        type: "docSidebar",
        sidebarId: "tutorialsSidebar",
        label: "Tutorials",
        position: "left",
      },
      {
        type: "docSidebar",
        sidebarId: "contributingSidebar",
        label: "Contributing",
        position: "left",
      },
      ],
    },
    footer: {
      style: 'dark',
      links: [
        {
          title: 'Links',
          items: [
            {
              href: 'https://www.deltablot.com/posts',
              label: 'Blog',
            },
            {
              href: 'https://www.deltablot.com/elabftw',
              label: 'Get hosted',
            },
            {
              href: 'https://www.elabftw.net',
              label: 'Official Website',
            },
          ],
        },
        {
          title: 'Community',
          items: [
            {
              label: 'Chat',
              href: 'https://gitter.im/elabftw/elabftw',
            },
            {
              label: 'Forum',
              href: 'https://github.com/elabftw/elabftw/discussions/',
            },
            {
              label: 'Fediverse',
              href: 'https://pouet.chapril.org/@deltablot',
            },
          ],
        },
        {
          title: 'More',
          items: [
            {
              label: 'Documentation source code',
              href: 'https://github.com/elabftw/documentation',
            },
            {
              label: 'Application source code',
              href: 'https://github.com/elabftw/elabftw',
            },
            {
              label: 'The ELN Consortium',
              href: 'https://the.elnconsortium.org',
            },
          ],
        },
      ],
      copyright: `Copyright © 2026 Deltablot SAS. Built with Docusaurus.`,
    },
    prism: {
      theme: prismThemes.github,
      darkTheme: prismThemes.dracula,
    },
  } satisfies Preset.ThemeConfig,
};

export default config;
