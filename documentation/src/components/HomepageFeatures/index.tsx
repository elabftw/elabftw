import type {ReactNode} from 'react';
import clsx from 'clsx';
import Heading from '@theme/Heading';
import styles from './styles.module.css';
import HomepageFAQ from '../HomepageFaq';

type FeatureItem = {
  title: string;
  image: string;
  description: ReactNode;
};

const FeatureList: FeatureItem[] = [
  {
    title: 'Easy to Install',
    image: require('@site/static/img/home-pic1.webp').default,
    description: (
      <>
        eLabFTW is easy to install with Docker. Get running in minutes.
      </>
    ),
  },
  {
    title: 'Easy to Use',
    image: require('@site/static/img/home-pic2.webp').default,
    description: (
      <>
        The detailed documentation and ergonomic interface make eLabFTW accessible to a wide variety of users.
      </>
    ),
  },
  {
    title: 'Open source',
    image: require('@site/static/img/home-pic3.webp').default,
    description: (
      <>
        eLabFTW is 100% open source and community-driven, licensed under AGPLv3.
      </>
    ),
  },
];

function Feature({title, image, description}: FeatureItem) {
  return (
    <div className={clsx('col col--4')}>
      <div className="text--center">
        <img src={image} alt={title} className={styles.featureSvg} />
      </div>
      <div className="text--center padding-horiz--md">
        <Heading as="h3">{title}</Heading>
        <p>{description}</p>
      </div>
    </div>
  );
}

export default function HomepageFeatures(): ReactNode {
  return (
    <section className={styles.features}>
      <div className="container">
        <div className="row">
          {FeatureList.map((props, idx) => (
            <Feature key={idx} {...props} />
          ))}
        </div>
      <HomepageFAQ />
      </div>
    </section>
  );
}
