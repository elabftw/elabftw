/**
 * @copyright 2026 Nicolas CARPi
 * @license AGPL-3.0
 * @package elabftw
 */
import { ChemicalMimeType } from 'ketcher-core';
import type { Ketcher } from 'ketcher-core';

export type ReactionRole = 'reactant' | 'reagent' | 'catalyst' | 'solvent' | 'product';
export type QuantityBasis = 'mass' | 'volume' | 'amount' | 'equivalents';
export const quantityUnits = { g: 'mass', mg: 'mass', mL: 'volume', uL: 'volume', mol: 'amount', mmol: 'amount', eq: 'equivalents' } as const;
export interface ReactionComponent {
  component_key: string;
  role: ReactionRole;
  name: string;
  molfile: string;
  smiles: string;
  inchi_key: string;
  molecular_formula: string;
  molecular_weight: string | number | null;
  compound_id: number | null;
  item_id: number | null;
  material_form: 'neat' | 'solution';
  purity_percent: string | number;
  density_g_ml: string | number | null;
  concentration_mol_l: string | number | null;
  coefficient: string | number;
  planned_basis: QuantityBasis;
  planned_unit: keyof typeof quantityUnits;
  planned_value: string | number | null;
  actual_basis: Exclude<QuantityBasis, 'equivalents'>;
  actual_unit: Exclude<keyof typeof quantityUnits, 'eq'>;
  actual_value: string | number | null;
}
export interface Reaction {
  id?: number;
  version?: number;
  title: string;
  scheme: { root: { nodes: Array<{ type?: string }> } } | null;
  rxn: string;
  preview_png: string;
  temperature_c: string | number | null;
  duration_s: string | number | null;
  pressure_pa: string | number | null;
  atmosphere: string;
  conditions_notes: string;
  reference_component_key: string | null;
  yield_basis_component_key: string | null;
  components: ReactionComponent[];
}

export const emptyComponent = (): ReactionComponent => ({
  component_key: crypto.randomUUID(), role: 'reagent', name: '', molfile: '', smiles: '', inchi_key: '',
  molecular_formula: '', molecular_weight: null, compound_id: null, item_id: null,
  material_form: 'neat', purity_percent: 100, density_g_ml: null, concentration_mol_l: null,
  coefficient: 1, planned_basis: 'mass', planned_unit: 'g', planned_value: null, actual_basis: 'mass', actual_unit: 'g', actual_value: null,
});

/** Parse the toolkit's explicit RXN V3000 roles and CTAB records. In particular,
 * never split SMILES on dots: a salt can be one reaction component. */
export function parseReactionRxn(rxn: string): Array<{ role: ReactionRole; molfile: string }> {
  const lines = rxn.replace(/\r/g, '').trimEnd().split('\n');
  if (lines[0] !== '$RXN V3000') throw new Error('Expected an RXN V3000 reaction.');
  const counts = lines.find(line => /^M {2}V30 COUNTS \d+ \d+/.test(line))?.match(/\d+/g)?.slice(1).map(Number);
  if (!counts || counts[0] < 1 || counts[1] < 1 || counts.reduce((sum, n) => sum + n, 0) > 128) {
    throw new Error('Draw a single reaction with at least one reactant and one product.');
  }
  const groups: Record<string, ReactionRole> = { REACTANT: 'reactant', PRODUCT: 'product', AGENT: 'reagent' };
  const result: Array<{ role: ReactionRole; molfile: string }> = [];
  const actualCounts = { reactant: 0, product: 0, reagent: 0 };
  let role: ReactionRole | undefined;
  let ctab: string[] | undefined;
  for (const line of lines.slice(1)) {
    const begin = line.match(/^M {2}V30 BEGIN (REACTANT|PRODUCT|AGENT)$/);
    const end = line.match(/^M {2}V30 END (REACTANT|PRODUCT|AGENT)$/);
    if (begin) {
      if (role || ctab) throw new Error('Invalid RXN section.');
      role = groups[begin[1]];
    } else if (end) {
      if (role !== groups[end[1]] || ctab) throw new Error('Invalid RXN section.');
      role = undefined;
    } else if (line === 'M  V30 BEGIN CTAB') {
      if (!role || ctab) throw new Error('Invalid RXN structure.');
      ctab = [line];
    } else if (ctab) {
      ctab.push(line);
      if (line === 'M  V30 END CTAB') {
        result.push({ role, molfile: '\n  eLabFTW\n\n  0  0  0     0  0            999 V3000\n' + ctab.join('\n') + '\nM  END\n' });
        actualCounts[role]++;
        ctab = undefined;
      }
    }
  }
  if (role || ctab || actualCounts.reactant !== counts[0] || actualCounts.product !== counts[1] || actualCounts.reagent !== (counts[2] ?? 0)) {
    throw new Error('Incomplete RXN reaction.');
  }
  return result;
}

/**
 * Each RXN entry becomes a component with the identifiers and properties Indigo can compute for it.
 * Calls to the standalone struct service must not overlap: it listens once per call type, so concurrent calls
 * of the same type lose their answer (and the promise never settles) or get the answer of another call.
 */
async function componentFromEntry(ketcher: Ketcher, entry: { role: ReactionRole; molfile: string }): Promise<ReactionComponent> {
  const smiles = await ketcher.structService.convert({ struct: entry.molfile, output_format: ChemicalMimeType.DaylightSmiles });
  // Generic structures and organometallics may not have usable identifiers or
  // masses. Preserve their structures and allow manual properties instead.
  const inchiKey = await ketcher.structService.getInChIKey(entry.molfile).catch(() => '');
  const properties = await ketcher.structService.calculate({ struct: entry.molfile, properties: ['molecular-weight', 'gross'] }).catch(() => null);
  const weight = Number(properties?.['molecular-weight']);
  return {
    ...emptyComponent(), ...entry,
    smiles: smiles.struct,
    inchi_key: inchiKey,
    molecular_weight: Number.isFinite(weight) && weight > 0 ? weight : null,
    molecular_formula: String(properties?.gross ?? ''),
  };
}

async function previewFromKet(ketcher: Ketcher, ket: string): Promise<string> {
  const blob = await ketcher.generateImage(ket, { outputFormat: 'png' });
  return new Promise<string>((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = () => resolve(String(reader.result).split(',')[1]);
    reader.onerror = () => reject(reader.error);
    reader.readAsDataURL(blob);
  });
}

/** A component reference survives a drawing change only if its component does */
function keptKey(key: string | null, components: ReactionComponent[]): string | null {
  return key !== null && components.some(component => component.component_key === key) ? key : null;
}

/** The reaction needs a reference, so the first reactant takes over a lost one */
function keptOrFirstReactant(key: string | null, components: ReactionComponent[]): string | null {
  return keptKey(key, components) ?? components.find(component => component.role === 'reactant')?.component_key ?? null;
}

export async function reactionFromKetcher(ketcher: Ketcher): Promise<Reaction> {
  const ket = await ketcher.getKet();
  const scheme: Reaction['scheme'] = JSON.parse(ket);
  // Multi-arrow drawings need an explicit stage model; never silently flatten them.
  if (scheme.root.nodes.filter(node => node.type === 'arrow').length !== 1) {
    throw new Error('Draw exactly one reaction arrow to create a reaction record.');
  }
  const rxn = await ketcher.getRxn('v3000');
  // one after the other, see componentFromEntry()
  const components: ReactionComponent[] = [];
  for (const entry of parseReactionRxn(rxn)) {
    components.push(await componentFromEntry(ketcher, entry));
  }
  const preview = await previewFromKet(ketcher, ket);
  const firstReactant = keptOrFirstReactant(null, components);
  return {
    title: '', scheme, rxn, preview_png: preview, temperature_c: null, duration_s: null,
    pressure_pa: null, atmosphere: '', conditions_notes: '',
    reference_component_key: firstReactant, yield_basis_component_key: firstReactant, components,
  };
}

/** Match occurrences, rather than collapsing duplicate molecules. Changed or
 * removed structures lose their old quantities only after explicit confirmation. */
export function replaceReactionDrawing(previous: Reaction, drawing: Reaction): Reaction {
  const remaining = [...previous.components];
  const components = drawing.components.map(component => {
    const matches = (old: ReactionComponent) => (component.inchi_key ? old.inchi_key === component.inchi_key : component.smiles === old.smiles)
      && (old.role === 'product') === (component.role === 'product');
    let match = remaining.findIndex(old => matches(old) && old.role === component.role);
    if (match < 0) match = remaining.findIndex(matches);
    if (match < 0) return component;
    const old = remaining.splice(match, 1)[0];
    return { ...old, molfile: component.molfile, smiles: component.smiles, inchi_key: component.inchi_key };
  });
  // Table-only additives are useful even if not drawn above the arrow.
  components.push(...remaining.filter(component => ['reagent', 'catalyst', 'solvent'].includes(component.role)));
  return {
    ...previous, scheme: drawing.scheme, rxn: drawing.rxn, preview_png: drawing.preview_png, components,
    reference_component_key: keptOrFirstReactant(previous.reference_component_key, components),
    // no yield basis is a valid choice, so a lost one is not replaced
    yield_basis_component_key: keptKey(previous.yield_basis_component_key, components),
  };
}
