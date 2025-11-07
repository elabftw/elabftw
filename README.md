<p align='center'>
  <img src='https://i.imgur.com/hq6SAZf.png' alt='elabftw logo' />
</p>

<p align='center'>
  <strong>A free, modern, versatile, secure electronic lab notebook for researchers</strong>
</p>

### [Official website](https://www.elabftw.net) | [Live demo](https://demo.elabftw.net) | [Documentation](https://doc.elabftw.net) | [Dedicated hosting](https://www.deltablot.com/elabftw)

[![CircleCI](https://circleci.com/gh/elabftw/elabftw/tree/master.svg?style=svg)](https://circleci.com/gh/elabftw/elabftw/tree/master)

[![Code Coverage](https://codecov.io/gh/elabftw/elabftw/branch/hypernext/graph/badge.svg?token=SHSZuaxt17)](https://codecov.io/gh/elabftw/elabftw)
[![CII Best Practices](https://bestpractices.coreinfrastructure.org/projects/2766/badge)](https://bestpractices.coreinfrastructure.org/projects/2766)
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
It is accessed _via_ the browser. Several research teams can be hosted on the same installation. This means **eLabFTW** can be installed at the institute level and host all team members at the same place. This is what is done at in many research institutions around the globe. It is also a great solution for companies looking into improving the traceability and reproducibility of their processes.

## Features

* Lab notebook for experiments
* Database for resources (lab reagents, equipment, storage, cell lines, chemical products, etc...)
* Trusted timestamping
* Blockchain timestamping
* Import and export in various formats
* Calendar to manage booking of equipment
* Support for various scientific file formats
* Molecule editor
* LaTeX support
* Todolist
* Public REST API
* Available in 21 languages
* Advanced permissions system
* Audited, secure codebase suitable for sensitive research results
* Self contained service that doesn't leak data to third party

## How it works

**eLabFTW** is designed to be installed on a server, and people from the team can log in from their browser.

![server client](https://i.imgur.com/BcfFgQS.gif)

# Installation

**eLabFTW** can easily be installed on any GNU/Linux server. It requires at least 512 MB of RAM (1 GB recommended) and 300 MB of disk space. Installation is done through Docker, so [Docker](https://www.docker.com/) needs to be installed on the server. Since it runs in a Docker container, php, webservers or mysql do not have to be installed on your server.  Everything is provided by the containers so different versions and extensions do not matter. This makes upgrading versions much easier.

The documentation explains everything:

## [Installation instructions](https://doc.elabftw.net)

# Contributing

**eLabFTW** is a collaborative project. Your contributions are very welcome! Have a look at the [contributing page](https://doc.elabftw.net/contributing.html) to see how you can help with translation or code.

# Security

See [SECURITY.md](./SECURITY.md).

# Support

## Community support

Free support is available through [GitHub issues](https://github.com/elabftw/elabftw/issues/new/choose). Please open an issue if you have a problem, want to make a suggestion, ask a question or discuss a feature.

You can also join the [chat room on Gitter](https://gitter.im/elabftw/elabftw).

## Commercial support

PRO support and professional hosting are available from [Deltablot](https://www.deltablot.com/elabftw).

# Stay tuned

[Subscribe to the newsletter](https://eepurl.com/bTjcMj) to be notified when a new release is out!

Or use GitHub to follow releases:

![release watch](https://i.imgur.com/whSAWEs.gif)

# Funding

eLabFTW is distributed without charge. If you find this project useful, please consider making a small donation either:

* as a [GitHub Sponsor](https://github.com/sponsors/NicolasCARPi)

* or with [Liberapay](https://liberapay.com/NicolasCARPi/donate)

## Gold sponsors

This project is backed by [Deltablot](https://www.deltablot.com) company.

<p align='center'>
  <a href="https://www.deltablot.com">
    <img src='https://i.imgur.com/9j2a9na.jpg' alt='deltablot logo' />
  </a>
</p>

* Atelier de la donnée ADOC Lorraine (projet financé avec le soutien du Fonds national pour la Science ouverte)
* Department for the Development of Innovative Digital Use – French National Research Institute for Sustainable Development
* Institut Curie – Centre de Recherche
* Vrije Universiteit Brussel
* Institute of Medical Biometry and Statistics, Faculty of Medicine and Medical Center – University of Freiburg, Germany
* Georg-August-University Goettingen - Institute of Inorganic Chemistry
* [Lablynx](https://www.lablynx.com/)

# Related projects

## API libraries

* [elabftw/elabapi-python](https://github.com/elabftw/elabapi-python/): python library for interacting with APIv2
* [elabftw/elabapi-javascript-example](https://github.com/elabftw/elabapi-javascript-example#readme): example code for interacting with APIv2 through JavaScript

## Communities

* [The ELN Consortium](https://github.com/TheELNConsortium/): a consortium of ELN vendors interested in improving interoperability between ELNs

## Importing data from another ELN

* [Importing data from RSpace](https://github.com/elabftw/rspace2elabftw): A python script to import .eln files from RSpace into eLabFTW
* [Importing data from LabFolder](https://github.com/TheELNConsortium/any2eln): A python script to import data from LabFolder into eLabFTW, using their API

## Third party libraries or scripts

These projects were created by users of eLabFTW, check them out if interested!

* [elAPI](https://github.com/uhd-urz/elAPI/): a powerful, extensible API client for eLabFTW developed at the University Computing Centre of University of Heidelberg
* [pyelabdata](https://github.com/FAU-PHYSIK-EP/pyelabdata): provides functions for simple one-line access to data stored in eLabFTW via Python
* [elabftw-teamupload](https://github.com/HeFDI-DE/elabftw-teamupload/): Synchronize teamgroups and teams with users from Excel
* [Reporting Dashboard](https://git.rwth-aachen.de/eln-rwth/reporting-scripts-public): Collection of scripts to create a reporting dashabord for eLabFTW SysAdmins

### Deprecated projects using retired api v1
* [din14970/elabftwqrprint](https://github.com/din14970/elabftwqrprint): python library to print QR codes from database objects
* [shabihsherjeel/nionswift_elabftw_plugin](https://github.com/shabihsherjeel/nionswift_elabftw_plugin): python plugin for [Nionswift](https://github.com/nion-software/nionswift)
* [iam-cms/elabapy-cli](https://gitlab.com/iam-cms/workflows/extra-nodes/elabapy-cli): command line interface for eLabFTW API
* [Elab::Client](https://metacpan.org/pod/ELab::Client): perl module for eLab API by Andreas K. Hüttel
* [LabView client](https://www.vipm.io/package/plasmapper_lib_pl_elabftw_client/): LabView client library by @plasmapper
* [Matlab client](https://github.com/baillon/eLabAPI): Matlab package to facilitate working with eLabFTW API


## Unofficial documentation

* [TU Graz support page](https://www.tugraz.at/sites/rdm/tools/elabftw/elabftw-support)
* [HeFDI eLabFTW Tutorials](https://ilias.uni-marburg.de/goto.php?target=crs_3174359&client_id=UNIMR)

## Contributors

<a href="https://github.com/elabftw/elabftw/graphs/contributors">
  <img src="https://contrib.rocks/image?repo=elabftw/elabftw" />
</a>

Made with [contrib.rocks](https://contrib.rocks).

## <3

Thank you for using **eLabFTW**. I hope it will bring happiness to your lab ;)
