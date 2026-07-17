import React, {useEffect, type ReactNode} from 'react';
import Details from '@theme/Details';
import {useLocation} from '@docusaurus/router';
import styles from './styles.module.css';

type FAQItem = {
  id: string;
  question: string;
  answer: ReactNode;
};

const FAQ: FAQItem[] = [
  {
     id: "why-use-elabftw",
     question: "Why use eLabFTW?",
     answer: (
      <>
        <ul>
          <li>
            <strong>It's free and open source software</strong>
          </li>
          <li>
            It improves the value of your experiments by allowing you to keep a good track of it
          </li>
          <li>It makes searching your data as easy as a google search</li>
          <li>Everything can be organized in your lab</li>
          <li>It makes it easy to share information between co-workers or collaborators</li>
          <li>It is simple to install and to keep up to date</li>
          <li>
            <strong>It works for Windows, Mac OS X, Linux, BSD, Solaris, etc…</strong>
          </li>
          <li>
            Protected access with login/password (password is very securely stored as salted SHA-512
            sum)
          </li>
          <li>It can be used by multiple users at the same time</li>
          <li>
            <strong>It can be used by multiple teams</strong>
          </li>
          <li>You can have templates for experiments you do often</li>
          <li>
            <strong>You can export an experiment as a PDF</strong>
          </li>
          <li>
            <strong>You can timestamp an experiment so it is legally strong</strong>
          </li>
          <li>You can export one or several experiments as a ZIP archive</li>
          <li>You can duplicate experiments in one click</li>
          <li>There is advanced search capabilities</li>
          <li>You can write in Markdown</li>
          <li>The tagging system allows you to keep track of family of experiments</li>
          <li>Experiments can have color coded statuses (that you can edit at will)</li>
          <li>
            You can link an experiment with an item from the database to retrieve in a click the
            plasmid/sirna/antibody/chemical you used
          </li>
          <li>
            And it works the other way around, you can find all experiments done with a particular
            item from the database!
          </li>
          <li>blockchain blockchain blockchain</li>
          <li>There is a locking mechanism preventing further edition</li>
          <li>You can comment on an experiment (if it's not your experiment)</li>
          <li>You can import your old database stored in an excel file</li>
          <li>You can use it in your language</li>
          <li>
            <a href="/docs/features">and much more…</a>
          </li>
        </ul>
        <p>
          You also have to consider the fact that installing eLabFTW on your own server means that
          no one will be able to snoop on your data. If you have ever used Evernote or basically any
          online ELN that says «Free», read carefully the privacy policy, you might be surprised
          what they allow themselves to do under the cover of «to improve your experience»…
        </p>
      </>
    ),
  },
  {
    id: "what-about-patents-and-intellectual-property",
    question: "What about patents and intellectual property?",
    answer: "eLabFTW allows legally-binding timestamping of your experiments. With just one click of a mouse, you can timestamp your work. There are currently two strategies available for timestamping: Trusted Timestamping (RFC3161) and Blockchain Timestamping.",
  },
  {
    id: "is-this-system-stable-can-i-trust-my-data-with-it",
    question: "Is this system stable? Can I trust my data with it?",
    answer: (
      <>
        <p>
          Yes. It is used in numerous research centers all over the world since more than a decade now and
          if an issue is found it is quickly reported and fixed.
        </p>
        <p>
          However, having an automated <a href="/docs/install/backup">backup</a> strategy is mandatory in
          order to be sure <strong>nothing will be lost</strong>.
        </p>
        <p>Being able to do backups is yet another advantage over paper (you can't backup paper!).</p>
      </>
    ),
  },
  {
    id: "who-else-is-using-it",
    question: "Who else is using it?",
    answer: (
      <>
        <p>
          We do not maintain a list of institutions using it anymore. There are just too many.
          Consider that there are thousands of eLabFTW instances worldwide.
        </p>
        <p>
          In France, it's the ELN of choice for the CNRS, the 1st largest research organization in
          Europe, along with many other universities and research institutes. It is also useful
          to startups and companies. You'll also find a lot of instances in German institutions, along with many other countries all around the world.
        </p>
      </>
    ),
  },
  {
    id: "is-the-data-encrypted",
    question: "Is the data encrypted?",
    answer: (
     <>
        <p>
          The data is encrypted when travelling from your browser to the server with the highest quality encryption currently available (TLSv1.2/1.3 with modern ciphers). The passwords are not recoverable in case of a breach and are hashed using state of the art algorithms.
        </p>
        <p>
          If you wish to have data at rest encryption, it needs to be done during the web server installation, and is not the concern of the software itself.
        </p>
      </>
    ),
  },
  {
    id: "is-elabftw-still-maintained",
    question: "Is eLabFTW still maintained?",
    answer: (
      <>
        <p>
          Yes. The project is actively maintained and updated. You can look at the release history
          on{" "}
          <a href="https://github.com/elabftw/elabftw/releases" target="_blank" rel="noreferrer">
            GitHub
          </a>
          .
        </p>
      </>
    ),
  },
  {
    id: "will-i-be-able-to-import-my-plasmids-antibodies-whatever-in-the-database-from-a-excel-file",
    question:
      "Will I be able to import my plasmids/antibodies/whatever in the database from a Excel file?",
    answer: (
      <>
        <p>
          Yes. You can import data into the database from a file, either from the web interface, or by using a dedicated import script. See{" "}
          <a href="/docs/tutorials/import-csv">Tutorial to import data programmatically</a>.
        </p>
      </>
    ),
  },
  {
    id: "can-i-try-it-before-i-install-it",
    question: "Can I try it before I install it?",
    answer: (
      <>
        <p>
          Yes. There is a demo instance available. See{" "}
          <a href="https://demo.elabftw.net" target="_blank" rel="noreferrer">
            demo.elabftw.net
          </a>
          .
        </p>
      </>
    ),
  },
  {
    id: "what-are-the-technical-requirements",
    question: "What are the technical requirements to install it?",
     answer: (
      <>
        <p>eLabFTW is a server software that should be installed on a server.</p>
        <h5>Requirements for the server</h5>
        <p>
          The following specification has been shown to perform well for an instance of several
          hundred daily users. The more users using the instance, the more CPU and RAM you'll need.
        </p>
        <p>
          <strong>Hardware</strong>
        </p>
        <p>
          At least 2Gb of RAM, a decent processor ({">"} 2GHz), preferably multi-core and an SSD
          disk with at least a few Gb free.
        </p>
        <p>
          <strong>Software</strong>
        </p>
        <p>
          The operating system of the server must be a flavor of GNU/Linux. We recommend Rocky
          Linux or RHEL.
        </p>
        <p>
          The service runs in containers (
          <a href="https://www.docker.com" target="_blank" rel="noreferrer">
            Docker
          </a>{" "}
          or{" "}
          <a href="https://podman.io" target="_blank" rel="noreferrer">
            Podman
          </a>
          ), which are a Linux specific technology.
        </p>
        <p>
          A <strong>MySQL</strong> database service is required. You can deploy a MySQL service with
          Docker by following the standard installation procedure, or use an existing separate MySQL
          server. Only <strong>MySQL</strong> is supported. Not PostgreSQL, not MariaDB, not SQLite.
        </p>
        <h5>Requirements for the clients (users)</h5>
        <ul>
          <li>
            Any operating system with either Firefox, or Chrome based browser (Chrome, Chromium,
            Edge, Brave, Opera). Safari is{" "}
            <a
              href="https://github.com/elabftw/elabftw/issues?q=is%3Aissue%20state%3Aclosed%20label%3A%22Safari%3A%20why%20are%20you%20like%20this%3F%22"
              target="_blank"
              rel="noreferrer"
            >
              known to cause issues
            </a>{" "}
            and is not officially supported. Internet Explorer is not supported.
          </li>
          <li>An internet connection.</li>
        </ul>
      </>
    ),
  },
  {
    id: "what-about-data-retention-traceability",
    question: "What about data retention/traceability",
    answer: `
There is a full audit log and revision history for entries. You can lock entries to prevent further edits. Timestamping and immutable archives can be used to strengthen traceability and integrity of records.
`,
  },
  {
    id: "is-it-compliant-to-21cfr-part-11",
    question: "Is it compliant to 21CFR Part 11?",
    answer: `
eLabFTW provides a number of features that help with compliance (audit trail, electronic signatures, access controls, immutable archives, etc.). Ultimately, compliance depends on how the system is configured, validated, and used within your organization.
`,
  },
  {
    id: "what-about-gmp-compliance",
    question: "What about GMP compliance?",
    answer: (
     <>
        <p>
          <strong>GMP Compliance Statement for eLabFTW</strong>
        </p>
        <h5>1. Introduction</h5>
        <p>
          eLabFTW is an open-source electronic lab notebook (ELN) and database designed for
          academic, industrial, and research institutions. This document outlines how eLabFTW
          complies with Good Manufacturing Practice (GMP) requirements, particularly in contexts
          where digital recordkeeping must meet regulatory standards.
        </p>
        <h5>2. System Overview</h5>
        <p>eLabFTW provides features essential for compliance with GMP, including:</p>
        <ul>
          <li>Secure and tamper-evident electronic recordkeeping</li>
          <li>Comprehensive audit trails</li>
          <li>User authentication and access control</li>
          <li>Version control of records</li>
          <li>Role-based permission management</li>
          <li>Timestamped entries</li>
        </ul>
        <h5>3. GMP-Relevant Compliance Features</h5>
        <h5>3.1 Data Integrity and Security</h5>
        <p>eLabFTW ensures data integrity through:</p>
        <ul>
          <li>Enforced HTTPS connections</li>
          <li>Encrypted data in transit</li>
          <li>Automatic backups</li>
          <li>Tamper-evident log entries</li>
        </ul>
        <h5>3.2 Audit Trails</h5>
        <p>
          Nearly every action performed in eLabFTW is logged. The audit trail includes:
        </p>
        <ul>
          <li>Timestamp of the action</li>
          <li>User identity</li>
          <li>Nature of the change (e.g., edit, delete)</li>
          <li>Linked records or experiments</li>
        </ul>
        <h5>3.3 User Management and Access Control</h5>
        <ul>
          <li>Each user has a unique login with secure password requirements</li>
          <li>Role-based access controls limit user permissions</li>
          <li>Admins can assign and revoke roles</li>
          <li>Inactive users can be disabled to maintain access security</li>
        </ul>
        <h5>3.4 Electronic Signatures</h5>
        <p>eLabFTW supports electronic signatures that are:</p>
        <ul>
          <li>Unique to each user</li>
          <li>Tied to a specific time and action</li>
          <li>Stored in immutable archives</li>
          <li>Using state of the art cryptography</li>
        </ul>
        <h5>3.5 Validation and Qualification</h5>
        <p>eLabFTW supports system validation through:</p>
        <ul>
          <li>Open-source codebase (fully inspectable)</li>
          <li>Documented update logs and change management</li>
        </ul>
        <h5>3.6 Data Retention and Exportability</h5>
        <ul>
          <li>Data can be exported in human-readable and machine-readable formats</li>
          <li>Archived data remains accessible and readable</li>
          <li>Long-term retention complies with GMP recordkeeping requirements</li>
        </ul>
        <h5>4. Implementation Best Practices</h5>
        <p>To ensure GMP compliance, administrators should:</p>
        <ul>
          <li>Implement validated backup strategies</li>
          <li>Maintain system validation documentation</li>
          <li>Define and enforce access policies</li>
          <li>Perform regular audits using eLabFTW's built-in reporting tools</li>
          <li>Train users on GMP principles and proper usage of eLabFTW</li>
        </ul>
        <h5>5. Conclusion</h5>
        <p>
          When properly configured and maintained, eLabFTW provides the necessary functionality to
          support GMP compliance in electronic documentation and laboratory recordkeeping
          environments.
        </p>
      </>
    ),
  },
  {
    id: "is-there-a-plugin-system",
    question: "Is there a plugin system?",
    answer: (
      <>
        <p>There is no plugin system, but there is an API.</p>
        <ul>
          <li>You can use the API to create scripts that interact with eLabFTW</li>
          <li>You can create external tools or integrations using the API</li>
        </ul>
      </>
    ),
  },
  {
    id: "is-it-totally-free",
    question: "Is it totally free?",
    answer: (
      <>
        <p>
          Yes, it is free software (as in speech) and also free as in beer. However, you will still
          need a server and technical know-how to deploy it securely.
        </p>
        <p>
          If you are looking for hosting (SaaS) options, check{" "}
          <a href="https://www.deltablot.com/elabftw" target="_blank" rel="noreferrer">
            Deltablot.com
          </a>
          .
        </p>
      </>
    ),
  },
  {
    id: "what-is-the-meaning-of-ftw",
    question: "What is the meaning of 'FTW'?",
    answer: (
      <>
        <p>One of those:</p>
        <ul>
          <li>For The World</li>
          <li>For Those Wondering</li>
          <li>For The Worms</li>
          <li>Forever Two Wheels</li>
          <li>Free The Wookies</li>
          <li>Forward The Word</li>
          <li>Forever Together Whenever</li>
          <li>Face The World</li>
          <li>Forget The World</li>
          <li>Free To Watch</li>
          <li>Feed The World</li>
          <li>Feel The Wind</li>
          <li>Feel The Wrath</li>
          <li>Fight To Win</li>
          <li>Find The Waldo</li>
          <li>Finding The Way</li>
          <li>Flying Training Wing</li>
          <li>Follow The Way</li>
          <li>For The Wii</li>
          <li>For The Win</li>
          <li>For The Wolf</li>
          <li>Free The Weed</li>
          <li>Free The Whales</li>
          <li>From The Wilderness</li>
          <li>Freedom To Work</li>
          <li>For The Warriors</li>
          <li>Full Time Workers</li>
          <li>Fabricated To Win</li>
          <li>Furiously Taunted Wookies</li>
          <li>Flash The Watch</li>
        </ul>
      </>
    ),
  },
];

function openAndScrollToHash(hash: string) {
  const id = (hash || '').slice(1);
  if (!id) return;

  const details = document.getElementById(id) as HTMLDetailsElement | null;
  if (!details) return;

  // If Docusaurus thinks it's collapsed, trigger its own handler by clicking summary
  const collapsed = details.getAttribute('data-collapsed') === 'true';
  const summary = details.querySelector('summary') as HTMLElement | null;

  if (collapsed && summary) {
    summary.click();
  } else {
    // Fallback for native behavior
    details.open = true;
  }

  details.scrollIntoView({behavior: 'smooth', block: 'start'});
}

export default function HomepageFAQ(): ReactNode {
  const location = useLocation();
   useEffect(() => {
    if (!location.hash) return;
    openAndScrollToHash(location.hash)
  }, [location.hash]);
  return (
    <section className={styles.faqSection}>
      <div className="container">
        <h2 className={styles.title}>Frequently Asked Questions</h2>

        <div className={styles.list}>
          {FAQ.map((item) => (
            <Details
              key={item.id}
              id={item.id}
              summary={item.question}
              className={styles.item}
               onToggle={(e) => {
                const el = e.currentTarget as HTMLDetailsElement;
                if (el.open) {
                  window.history.replaceState(null, '', `#${item.id}`);
                }
              }}
            >
              {item.answer}
            </Details>
          ))}
        </div>
      </div>
    </section>
  );
}
