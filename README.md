<p align='center'>
  <img src='https://i.imgur.com/hq6SAZf.png' alt='elabftw logo' />
</p>

<p align='center'>
  <strong>A free, modern, flexible electronic lab notebook for researchers</strong>
</p>

[Official website](https://www.elabftw.net) | [Live demo](https://demo.elabftw.net) | [Documentation](https://doc.elabftw.net)

[![CircleCI](https://circleci.com/gh/elabftw/elabftw/tree/master.svg?style=svg)](https://circleci.com/gh/elabftw/elabftw/tree/master)

[![Codacy Badge](https://api.codacy.com/project/badge/Grade/9942fbe6c6f14f488be69e51e93a1c10)](https://www.codacy.com/app/elabftw/elabftw)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/elabftw/elabftw/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/elabftw/elabftw/?branch=master)
[![Join the chat at https://gitter.im/elabftw/elabftw](https://badges.gitter.im/elabftw/elabftw.svg)](https://gitter.im/elabftw/elabftw?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)
[![License](https://img.shields.io/badge/license-AGPL-blue.svg)](https://www.gnu.org/licenses/agpl-3.0.en.html)
[![Release](https://img.shields.io/github/release/elabftw/elabftw.svg)](https://github.com/elabftw/elabftw/releases/latest)
[![StackShare](https://img.shields.io/badge/tech-stack-0690fa.svg?style=flat)](https://stackshare.io/elabftw/elabftw)
[![Joss](http://joss.theoj.org/papers/10.21105/joss.00146/status.svg)](http://joss.theoj.org/papers/10.21105/joss.00146)

# Description

- Tired of that shared excel file for your antibodies or plasmids?

- Want to be able to search in your past experiments as easily as you would do it on google?

- Want an electronic lab notebook that lets you timestamp legally your experiments?

Then you are at the right place!

**eLabFTW** is an electronic lab notebook manager for research teams. It features a database where any kind of objects (such as antibodies, plasmids, cell lines, boxes, _etc_.) can be stored.
It can be accessed _via_ the browser. Several research teams can be hosted on the same installation. This means **eLabFTW** can be installed at the institute level and host all team members at the same place. This is what is done at [Institut Curie](http://www.curie.fr) and in several other research centers around the globe.

Click the image below to see it in bigger size:
[![Demo gif](https://i.imgur.com/pH6Qvwf.gif)](https://gfycat.com/WillingIncompleteAstarte)

**eLabFTW** is designed to be installed on a server, and people from the team can log in from their browser.

![server client](https://i.imgur.com/BcfFgQS.gif)

Without a server, even an old computer with 1 GB of RAM and an old CPU can be used. Make sure to install a recent GNU/Linux distribution on it and plug it to the intranet.

# Installation

**eLabFTW** can easily be installed on any GNU/Linux server. It requires at least 512 MB of RAM (1 GB recommended) and 300 MB of disk space. Installation is done through Docker, so [Docker](https://www.docker.com/) needs to be installed on your computer. Since it runs in a Docker container, php, webservers or mysql do not have to be installed on your server.  Everything is provided by the container so different versions and extensions do not matter.

The documentation explains everything:

## [Installation instructions](https://doc.elabftw.net)

# Contributing

**eLabFTW** is a collaborative project. See the [contributing page](https://doc.elabftw.net/contributing.html).

If you have found a security issue, please report it by email to the address associated to my GPG key:

~~~bash
gpg --search-keys "Nicolas CARPi"
~~~

# Support

Free support is available through [GitHub issues](https://github.com/elabftw/elabftw/issues/new). Please open an issue if you have a problem, want to make a suggestion, ask a question or discuss a feature.

You can also join the [chat room on Gitter](https://gitter.im/elabftw/elabftw).

Pro support, hosting or commercial license are available from [Deltablot](https://www.deltablot.com/elabftw).

[Subscribe to the newsletter](http://eepurl.com/bTjcMj) to be notified when a new release is out!

# Funding

This project is sponsored by [Institut Curie](https://science.curie.fr/).

Thank you for using **eLabFTW**. I hope it will bring happiness to your lab ;)
