# eLabFTW project governance

eLabFTW is a community-driven free and open-source project governed by [Deltablot](https://www.deltablot.com). The software remains freely available to all users, while responsibility for the project's strategic direction, maintenance, quality, and release process is held by members of Deltablot.

This governance model provides clear accountability and a documented process for decisions, software changes, and releases. It supports the continued maintenance of eLabFTW, including bug fixes, security updates, and new features, while enabling developers to work on the project full-time.

## Governance objectives

The governance and development processes are intended to provide:

- clear ownership and accountability;
- controlled and traceable software changes;
- independent review and approval;
- documented testing and verification;
- restricted merge and release permissions; and
- a reviewable history of decisions, changes, and releases.

For organisations operating in a regulated environment, these controls may be used as supporting evidence during supplier assessment and risk-based validation activities. They do not, by themselves, make a particular installation or use of eLabFTW compliant with GxP requirements.

## Key contributors

- @NicolasCARPi (Admin)
- @MoustaphaCamara (Admin)

## Deltablot employees

- Sydney Koke @sjkoke: Customer Success Manager and Community Manager
- Moustapha Camara @MoustaphaCamara: Full-stack Developer
- Elsa Touma @eltouma: Full-stack Developer

## Decision-making and accountability

@NicolasCARPi acts as the project's benevolent dictator and has final authority over strategic and technical decisions.

Where appropriate, issues, discussions, or polls are used to collect input from contributors and users. Decisions that result in software changes must be documented in the associated issue or pull request, including the decision rationale and expected impact. Commit history and release notes may provide supplemental evidence, but do not replace this decision record.

## Controlled code change procedure

All code changes are developed on a separate branch and submitted through a pull request. Direct changes to the project's controlled branches are not part of the normal development process.

### Change description and traceability

A pull request must provide enough information for reviewers to understand the change. Depending on the nature and risk of the change, this may include:

- the purpose and scope of the change;
- a link to the related issue or requirement;
- the affected components and expected behaviour;
- potential impact on security, data integrity, compatibility, or existing functionality;
- database or configuration changes;
- the tests performed; and
- relevant documentation or release-note updates.

The pull request, its commits, review discussions, approval status, and merge record provide a traceable history of the change.

### Independent review and approval

Every pull request must be reviewed and explicitly approved by at least one reviewer other than the author before it can be merged.

The review process may involve several rounds of comments, corrections, and additional testing. Reviewers consider the correctness and maintainability of the change and, where applicable, its impact on:

- security and access control;
- data integrity and auditability;
- authentication and authorisation;
- database migrations and data compatibility;
- existing and regulated workflows;
- test coverage; and
- user and technical documentation.

Unresolved review comments must be addressed before approval. Approval is recorded in the pull request.

### Testing and verification

Changes must be tested before they are merged. The extent of testing is proportionate to the nature, complexity, and potential impact of the change.

Documented acceptance criteria must be defined for the change. Applicable automated checks must complete successfully, and additional manual, integration, migration, regression, or security testing is performed when warranted by the change. Evidence of the tests performed and their results must be recorded in, or linked from, the pull request or associated change record.

Test failures, identified deviations, and deferred testing must be documented with an assessment of their impact, a risk-based rationale for the proposed disposition, and the name of the approver. Before the change is approved, each item must either be closed or be covered by a formal, documented risk acceptance that identifies the residual risk, any compensating controls, a named owner, and a deadline for resolution or completion.

### Merge control

Only authorised maintainers may merge approved pull requests.

Currently, only @NicolasCARPi can merge changes into the `master` branch and create new software releases.

A change may be merged only when:

1. the pull request contains sufficient information to review the change;
2. the required review has been completed;
3. a reviewer has explicitly approved the change;
4. applicable tests and automated checks have passed; and
5. blocking comments or identified issues have been resolved.

## Changes with increased impact

Changes affecting security, authentication, authorisation, audit trails, electronic signatures, data integrity, database structure, data migration, or other critical functionality require particular attention during review and testing.

The level of review and verification is based on the potential impact of the change. Additional reviewers or evidence may be requested where appropriate.

## Urgent and security-related changes

Urgent corrections and security fixes may follow an expedited process, but they remain subject to the same fundamental controls:

- the change is submitted through a pull request;
- the reason and scope of the change are documented;
- testing is performed to the extent reasonably possible before merging;
- any unresolved deviation or deferred testing is documented and formally risk-accepted before merging;
- approval by a reviewer is required before merging;
- the resulting change remains traceable to its commit and release;
- any deferred testing is completed after the merge by a named owner within a documented deadline, with the results recorded in or linked from the pull request or associated change record; and
- a retrospective review is completed after the merge by a named owner within a documented deadline to confirm the effectiveness of the change, review any remaining risk acceptance, and determine whether further corrective action is required.

Security-related information may be kept confidential until a fix is available, but the change remains subject to review and approval by authorised maintainers.

## Release control and traceability

Software releases are created from reviewed and approved changes.

Each release is assigned a version and linked to a protected release reference consisting of the version tag and the exact source commit from which the release was produced. Modification or deletion of these references is restricted to authorised maintainers and must remain traceable.

Applicable build manifests, artifact identifiers, package or container image digests, checksums, signatures, and provenance references are retained so that the released artifacts can be unambiguously associated with their source code and build.

The authoritative release record is the release entry associated with the protected version tag. It identifies the released version, source commit, release date, responsible releaser or approver, retained manifests or artifact identifiers, and release notes. Commit history, merged pull requests, and external artifact catalogues provide supplemental evidence.

Published fixes and functional changes are delivered through a new version rather than by silently modifying the source code associated with an existing release.

## GxP context

This governance process is designed to provide evidence of controlled software development and change management. In particular, it supports principles commonly expected for computerised systems used in regulated environments, including:

- documented and controlled changes;
- defined responsibilities and restricted permissions;
- traceability from a change request to implementation and release;
- review and approval by a person other than the author;
- testing proportionate to risk;
- preservation of change and release records; and
- assessment of changes affecting security or data integrity.

The governance process applies to the development and release of the eLabFTW software. The regulated organisation remains responsible for determining whether eLabFTW is suitable for its intended use and for validating its own implementation.

Customer responsibilities normally include, as applicable:

- defining intended use and user requirements;
- performing a documented risk assessment;
- validating the configured system and relevant workflows;
- controlling user access and responsibilities;
- establishing operating procedures and training;
- managing infrastructure, configuration, backups, and business continuity;
- assessing upgrades before deployment;
- maintaining the validated state of the operational system; and
- complying with the regulations applicable to its activities and jurisdiction.

## References

- [EU GMP Annex 11: Computerised Systems](https://health.ec.europa.eu/system/files/2016-11/annex11_01-2011_en_0.pdf)
- [FDA guidance: Part 11, Electronic Records; Electronic Signatures — Scope and Application](https://www.fda.gov/regulatory-information/search-fda-guidance-documents/part-11-electronic-records-electronic-signatures-scope-and-application)
- [MHRA GxP Data Integrity Guidance and Definitions](https://www.gov.uk/government/publications/guidance-on-gxp-data-integrity)
