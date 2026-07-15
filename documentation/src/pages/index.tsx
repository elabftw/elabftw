import type {ReactNode} from 'react';
import clsx from 'clsx';
import Link from '@docusaurus/Link';
import {
  useDocsPreferredVersion,
  useLatestVersion,
} from '@docusaurus/plugin-content-docs/client';
import useDocusaurusContext from '@docusaurus/useDocusaurusContext';
import Layout from '@theme/Layout';
import HomepageFeatures from '@site/src/components/HomepageFeatures';
import Heading from '@theme/Heading';

import styles from './index.module.css';

function HomepageHeader(): ReactNode {
  const {siteConfig} = useDocusaurusContext();
  const latestVersion = useLatestVersion(undefined);
  const {preferredVersion} = useDocsPreferredVersion();

  const version = preferredVersion ?? latestVersion;

  const getDocPath = (id: string): string => {
    const doc = version.docs.find(candidate => candidate.id === id);

    if (!doc) {
      throw new Error(
        `Documentation page "${id}" does not exist in version "${version.name}".`,
      );
    }

    return doc.path;
  };

  return (
    <header className={clsx('hero hero--primary', styles.heroBanner)}>
      <div className="container">
        <Heading as="h1" className="hero__title">
          {siteConfig.title}
        </Heading>

        <p className="hero__subtitle">{siteConfig.tagline}</p>

        <div className={styles.buttons}>
          <Link
            className="button button--secondary button--lg"
            to={getDocPath('install/intro')}>
            Install it
          </Link>

          <Link
            className="button button--secondary button--lg"
            to={getDocPath('usage/intro')}>
            Use it
          </Link>
        </div>
      </div>
    </header>
  );
}

export default function Home(): ReactNode {
  const {siteConfig} = useDocusaurusContext();

  return (
    <Layout
      title={siteConfig.title}
      description="Official eLabFTW documentation">
      <HomepageHeader />

      <main>
        <HomepageFeatures />
      </main>
    </Layout>
  );
}
