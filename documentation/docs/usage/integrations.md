---
sidebar_position: 30
title: Integrations
---

# Integrations with external services

## PubChem

The [PubChem](https://pubchem.ncbi.nlm.nih.gov/) database is directly integrated in eLabFTW on the Compounds page. It allows users to import chemical compounds in eLabFTW with information such as:

- CAS Number
- Molecular formula
- Molecular weight
- InChI
- SMART
- Hazard information
- and many more properties

## Bloxberg

The [Bloxberg](https://bloxberg.org/) blockchain is integrated in eLabFTW. Click the blockchain button from the main toolbar of an entry to add its hash to the blockchain and get a Research Object Certificate back.

## Keeex

eLabFTW provides a way to use a [Keeex](https://keeex.me/) service during the timestamp action. It is a technology that helps guarantee the integrity and authenticity of files autonomously and without relying on a third party.

When enabled, the generated pdf for timestamping will be sent over to a Keeex Fusion service running on your network in http mode for keeex/timestamping of the pdf, before being timestamped via your RFC3161 provider.

If you would like to use this service, please [contact us](https://www.deltablot.com/contact).

## InvenioRDM

Entries can be exported as an [.eln archive](import-export.md) to a draft record on an [InvenioRDM](https://inveniordm.docs.cern.ch/) repository.

### Configuration by a Sysadmin

In the Sysadmin panel, section "InvenioRDM repository configuration":

- **Host**: base URL of the InvenioRDM instance, for example `https://data.example.org`. The export is disabled for everyone as long as this is empty.
- **Repository name**: label shown in the export menu and modal. Defaults to "InvenioRDM".

### Personal token

Each user must create a personal access token in their InvenioRDM profile (Settings → Applications) with the permissions required to create records. Paste it in the User Control Panel, "InvenioRDM API Token". The token is stored encrypted, is never returned by the API and can be removed with the "Clear" button.

### Exporting

Open the export menu of an experiment or resource and choose "Export to InvenioRDM" (the name set by the Sysadmin). Fill in the title, author, date, description, resource type and license, optionally select a community, and submit. eLabFTW creates a draft record, uploads the .eln archive of the entry and adds a link to the draft in the extra fields of the entry. The draft is not published: review and publish it in InvenioRDM.
