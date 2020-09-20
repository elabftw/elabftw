<p align='center'>
  <img src='https://i.imgur.com/hq6SAZf.png' alt='elabftw logo' />
</p>

<p align='center'>
  <strong>A free, modern, flexible electronic lab notebook for researchers</strong>
</p>

[Official website](https://www.elabftw.net) | [Live demo](https://demo.elabftw.net) | [Documentation](https://doc.elabftw.net) | [Dedicated hosting](https://www.deltablot.com/elabftw)

[![CircleCI](https://circleci.com/gh/elabftw/elabftw/tree/master.svg?style=svg)](https://circleci.com/gh/elabftw/elabftw/tree/master)

[![Codacy Badge](https://api.codacy.com/project/badge/Grade/9942fbe6c6f14f488be69e51e93a1c10)](https://www.codacy.com/app/elabftw/elabftw)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/elabftw/elabftw/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/elabftw/elabftw/?branch=master)
[![Join the chat at https://gitter.im/elabftw/elabftw](https://badges.gitter.im/elabftw/elabftw.svg)](https://gitter.im/elabftw/elabftw?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)
[![License](https://img.shields.io/badge/license-AGPL-blue.svg)](https://www.gnu.org/licenses/agpl-3.0.en.html)
[![Release](https://img.shields.io/github/release/elabftw/elabftw.svg)](https://github.com/elabftw/elabftw/releases/latest)
[![StackShare](https://img.shields.io/badge/tech-stack-0690fa.svg?style=flat)](https://stackshare.io/elabftw/elabftw)
[![Joss](http://joss.theoj.org/papers/10.21105/joss.00146/status.svg)](http://joss.theoj.org/papers/10.21105/joss.00146)<br>
[![Carbon offset](https://img.shields.io/ecologi/carbon/deltablot)](https://ecologi.com/deltablot)
[![Trees](https://img.shields.io/ecologi/trees/deltablot)](https://ecologi.com/deltablot)
[![Plant more](https://img.shields.io/badge/trees-plant%20more%20trees-brightgreen)](https://ecologi.com/?r=5f668f69232cc100192fbd04)

# Description

**eLabFTW** is an electronic lab notebook manager for research teams.

It lets you store and organize your research experiments easily. It also features a database where any kind of objects (such as antibodies, plasmids, cell lines, boxes, _etc_.) can be stored.
It is accessed _via_ the browser. Several research teams can be hosted on the same installation. This means **eLabFTW** can be installed at the institute level and host all team members at the same place. This is what is done at [Institut Curie](http://www.curie.fr) and in several other research centers around the globe.

Click the image below to see it in bigger size:
[![Demo gif](https://i.imgur.com/pH6Qvwf.gif)](https://gfycat.com/WillingIncompleteAstarte)

**eLabFTW** is designed to be installed on a server, and people from the team can log in from their browser.

![server client](https://i.imgur.com/BcfFgQS.gif)

Without a server, even an old computer with 1 GB of RAM and an old CPU can be used. Make sure to install a recent GNU/Linux distribution on it and plug it to the intranet.

# Installation

**eLabFTW** can easily be installed on any GNU/Linux server. It requires at least 512 MB of RAM (1 GB recommended) and 300 MB of disk space. Installation is done through Docker, so [Docker](https://www.docker.com/) needs to be installed on the server. Since it runs in a Docker container, php, webservers or mysql do not have to be installed on your server.  Everything is provided by the containers so different versions and extensions do not matter. This makes upgrading versions much easier.

The documentation explains everything:

## [Installation instructions](https://doc.elabftw.net)

# Contributing

**eLabFTW** is a collaborative project. Your contributions are very welcome! Have a look at the [contributing page](https://doc.elabftw.net/contributing.html) to see how you can help with translation or code.

If you have found a security issue, please report it by email to the address associated to my GPG key:

~~~bash
gpg --search-keys "Nicolas CARPi"
~~~

# Support

Free support is available through [GitHub issues](https://github.com/elabftw/elabftw/issues/new/choose). Please open an issue if you have a problem, want to make a suggestion, ask a question or discuss a feature.

You can also join the [chat room on Gitter](https://gitter.im/elabftw/elabftw).

Pro support, dedicated hosting or commercial license are available from [Deltablot](https://www.deltablot.com/elabftw).

[Subscribe to the newsletter](http://eepurl.com/bTjcMj) to be notified when a new release is out!

Or use GitHub to follow releases:

![release watch](https://i.imgur.com/whSAWEs.gif)

# Funding

As you know, eLabFTW is distributed without charge.

If you find this project useful, please consider making a small donation either:

* as a [GitHub Sponsor](https://github.com/sponsors/NicolasCARPi)

* or with [Liberapay](https://liberapay.com/NicolasCARPi/donate)

This project is backed by [Institut Curie](https://science.curie.fr/) and [Deltablot](https://www.deltablot.com).

<p align='center'>
  <a href="https://www.deltablot.com">
    <img src='https://i.imgur.com/9j2a9na.jpg' alt='deltablot logo' />
  </a>
</p>

Thank you for using **eLabFTW**. I hope it will bring happiness to your lab ;)

# Related projects

These projects were created by users of eLabFTW, check them out if interested!

* [din14970/elabftwqrprint](https://github.com/din14970/elabftwqrprint): python library to print QR codes from database objects
* [shabihsherjeel/nionswift_elabftw_plugin](https://github.com/shabihsherjeel/nionswift_elabftw_plugin): python plugin for [Nionswift](https://github.com/nion-software/nionswift)
