---
sidebar_position: 5
title: Compounds
---

# Compounds

A chemical compounds database is available to all users in all teams, it is a common database for the instance, storing references of all existing compounds. Note that the visibility cannot be changed. Compounds are always visible to everyone with access to the instance. You can access it from the Tools menu.

<figure>
  <img src="/img/compounds-db.png" alt="compounds-db" />
  <figcaption>The shared compounds database.</figcaption>
</figure>

Compounds have specific properties such as a CAS number or a SMILES/InChI representation. They can also be associated with safety risks. Once a compound is present in the local eLabFTW database, it can be referenced (linked) from an Experiment or a Resource.

## Importing a compound from PubChem

Compounds can be imported from PubChem. From the Compounds page, click the "Import from PubChem" button, you are presented with a modal window:

<figure>
  <img src="/img/compounds-import-pubchem-modal.png" alt="compounds-import-pubchem-modal" />
  <figcaption>Importing compounds from PubChem.</figcaption>
</figure>

You can specify a PubChem CID (a unique identifier for all compounds present in PubChem), or a CAS number (a unique identifier present for most compounds). Input the number (CID or CAS) and click Search. A preview of the data is displayed below. If that is the correct compound, click Import.

Your newly imported compound is now visible in the table listing them all. Double-click it to further edit its properties.

<figure>
  <img src="/img/compounds-edit.png" alt="compounds-edit" />
  <figcaption>Editing attributes of a compound.</figcaption>
</figure>


From this window, you can edit all the properties of the compound. Only the "Name" is a mandatory field, all other fields are optional.

The safety section allows you to define health hazards associated with that compound, and also if it is a controlled substance such as a drug precursor, or nanomaterials.

<figure>
  <img src="/img/compounds-safety.png" alt="compounds-safety" />
  <figcaption>Editing safety information for a compound.</figcaption>
</figure>

Now that your compound is correctly created, you can click the "Create resource from compound" button on top of this modal window to create a Resource linked with that compound. That resource can be seen as an instantiation of this abstract compound that is present in this common, shared compound database.

With a Resource, you can set permissions and also add more information, attach files, define inventory, and link to other Resources or Experiments.

A Resource (or an Experiment) can be linked to one or several existing Compound, which allows you to create a Resource representing a mixture of compounds.

## Creating a compound manually

Maybe you've just created an never-seen before chemical compound, which means you cannot import it from PubChem. In this case, click the "Add compound" button to manually add a compound. Be aware, as mentioned above, the new compound will be visible by all users of the instance. In case you want to keep it confidential, do not add it.

## Importing compounds manually

Look at the [Import compounds through CLI](../../tutorials/import-compounds.md) section to learn how to import your compounds from a spreadsheet file or through the API.

## Fingerprints

When you add a compound where the SMILES representation is defined, and if the instance is configured to use the Fingerprinting service, a fingerprint of the compound will be stored in the database, allowing the search for substructures.

## Chemical structure editor

Since version 5.2, a chemical structure editor is present in the Tools menu. It allows one to draw molecules and perform operation on them such as searching the compounds database for similar molecules, via a substructure search. This feature requires the compounds present in the common database to be associated with a fingerprint, which is the case if the fingerprinting service is active, and the compounds have a SMILES representation defined.

<figure>
  <img src="/img/compounds-editor.png" alt="compounds-editor" />
  <figcaption>Using the editor to draw, import or export molecules.</figcaption>
</figure>

For detailed instructions, click the (?) icon at the top of the editor.

<figure>
  <img src="/img/compounds-editor-help.png" alt="compounds-editor-help" />
  <figcaption>Editor documentation.</figcaption>
</figure>

## Recording reactions in an entry

The chemical editor is also available while editing experiments, resources and
their templates. It shares its place with the main text: use the tabs above the
main text to switch to **Chemical editor and reactions**. The selected tab is
remembered, so other entries open on the same view. Draw or load a reaction with
one arrow, then click **Create reaction**. Review the detected reactants, products and reagents, enter a title,
and save the reaction. Saving an editor attachment alone does not create a
structured reaction record.

The reaction table stores the chemical structures and properties locally in the
entry. Known structures are matched to existing Compounds using their InChIKey.
Novel structures are not automatically added to the shared Compounds database.
Later edits to a Compound do not change an existing reaction's calculations.

Use **Properties and links** to set molar mass, purity, density, stock molarity
and stoichiometric coefficients, or to link a Compound or a particular Resource
(bottle, batch or sample). Additional reagents, catalysts and solvents can be
added to the table even when they are not drawn in the scheme. Linking a Resource
does not consume stock or change its inventory record.

### Planning and recording quantities

Planned quantities and actual quantities are independent. Choose the quantity
you entered: mass (g or mg), volume (mL or µL), or amount (mol or mmol). Planned quantities can
also be expressed in equivalents. The other quantities are calculated without
overwriting the entered value. Unknown values remain blank.

For neat materials, calculations use molar mass, purity and density. For stock
solutions, select **Solution** and enter the stock molarity. A volume is converted
to amount using that molarity. Converting a solution's mass also requires its
density. Purity is not applied a second time to the stock molarity.

Select the reference material defining **1 equivalent** separately from the
**yield basis**, normally the limiting material. Isolated product yield is
calculated from actual amounts and stoichiometric coefficients. The application
does not infer the limiting material or the balanced equation from the drawing.
Missing amounts or a missing yield basis leave yield blank.

### Editing and preserving reactions

Use **Edit** on a registered reaction to modify its table and conditions. To
change its drawing, load it into the editor, make the change, open its reaction
form, and choose **Use current editor drawing**. Matching molecular occurrences
keep their quantities; changed structures are new components. Review the table
before saving. Changing the standalone editor canvas does not automatically
change a registered reaction.

Reactions inherit their entry's permissions and locking. Duplicating an entry or
creating one from a template copies its reactions independently, including their
recorded quantities. Reactions appear in view mode, PDFs, JSON exports and ELN
archives. ELN import preserves local structures and quantities but clears links
to installation-specific Compound and Resource IDs.

Each save or deletion of a reaction records an immutable snapshot on the
instance, so the history of a reaction is kept even after it is deleted. This
history is not available to anonymous users. Like the revisions of the entry
body, it stays on the instance where it was recorded: ELN archives contain the
current reactions only, and an imported reaction starts a new history.

### Searching reactions

On the Experiments and Resources pages, click **Search reactions** below the
search bar. The entries listed are the ones with a reaction matching all the
criteria entered, combined with the other filters of the page (category, owner,
tags, scope…). Deleted reactions are not searched.

- **Reaction or component**: text found in the title, conditions or atmosphere
  of a reaction, or in the name or molecular formula of one of its components.
- **Structure**: a SMILES finds the components containing this substructure.
  The search compares structural fingerprints, like the compounds substructure
  search, so a few results can contain a similar structure only. An InChIKey
  finds the same molecule, including its other stereoisomers or protonation
  states. Check **Exact structure** to find only the identical structure.
  Select **SMARTS** in front of the field to enter a SMARTS pattern instead,
  for instance `[OX2H]c` for the components with a phenol group. The pattern
  is matched exactly, without fingerprint screening. When too many components
  are candidates, the search asks you to select a role or add other criteria.
- **Role**: only look for the structure among the reactants, products, reagents,
  catalysts or solvents.

From a reaction, click **Find experiments with the same reaction** to list the
experiments with the same reactants and products, whatever the reagents,
catalysts, solvents or conditions. Each component with an InChIKey also has a
search icon to find the experiments using this compound.

When a reaction is saved, eLabFTW stores a reaction SMILES
(`reactants>agents>products`), a key identifying its transformation, and a
fingerprint of each component structure. A structure that cannot be
fingerprinted is still saved, but a substructure search cannot find it; a
Sysadmin can compute the missing fingerprints with
`bin/console reactions:fingerprint`. The transformation key is only computed
when every reactant and product has an InChIKey.

### Reaction API

Use `api/v2/{entity_type}/{id}/reactions`, where `entity_type` is `experiments`,
`items`, `experiments_templates`, or `items_types`.

- `GET`: read current reactions and their components.
- `POST`: create a reaction, supplying a `components` array with local chemical
  properties and quantities. A component has a stable `component_key` UUID.
- `GET /{reaction_id}`: read one reaction scoped to its owning entry.
- `PATCH /{reaction_id}`: update a reaction. Supply the last read `version` to
  avoid overwriting a concurrent edit. Omitted fields are retained; a supplied
  `components` array replaces the component list.
- `DELETE /{reaction_id}`: delete the reaction while preserving its history.
- `GET ?history_of={reaction_id}`: read immutable revision snapshots, including
  those of deleted reactions. Read access to the owning entry is required, and
  anonymous users cannot read it.
- `PATCH` on the collection with `action: "calculate"`: validate quantities and
  return calculated components without saving anything.

A reaction also has two read-only fields: `reaction_smiles` and `reaction_key`
(null when a reactant or product has no InChIKey).

To search entries by reaction, add these query parameters to
`GET api/v2/{entity_type}`: `reaction_q` (text), `reaction_structure` (SMILES or
InChIKey), `reaction_structure_type` (`smarts` to give `reaction_structure` as a
SMARTS pattern), `reaction_exact` (`1` for identical structures only), `reaction_role`,
`reaction_compound` (linked compound id), `reaction_item` (linked resource id)
and `reaction_key`. The role applies to the structure, compound and resource
criteria, which must all match the same component.

Each planned or actual quantity has a `*_basis` (`mass`, `volume`, `amount`, or
planned-only `equivalents`) and a nullable `*_value`. Optional `planned_unit` and `actual_unit` preserve the
entered unit (`g`, `mg`, `mL`, `uL`, `mol`, `mmol`, or planned-only `eq`); their
defaults follow the basis. `reference_component_key`
and `yield_basis_component_key` must identify reactants or reagents in that
reaction. Calculated values use g, mL, mol, g/mol, g/mL, mol/L, seconds, °C and
absolute pressure in Pa. Purity is expressed as a percentage. Decimal inputs
support up to 12 fractional places.

The first implementation supports one transformation per reaction record.
Multi-stage reactions, screening matrices, analytical yields and automatic stock
consumption require additional models and are not inferred from a drawing.
