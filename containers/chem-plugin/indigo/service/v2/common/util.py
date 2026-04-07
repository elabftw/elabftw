# Copyright 2026 Nicolas CARPI - Deltablot
# Originally from epam/indigo (Apache 2.0)
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
#     http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.
def highlight(indigo, target, query):
    query.aromatize()
    matcher = indigo.substructureMatcher(target)
    match = matcher.match(query)
    if not match:
        return target
    for qatom in query.iterateAtoms():
        atom = match.mapAtom(qatom)
        atom.highlight()
        for nei in atom.iterateNeighbors():
            if (
                not nei.isPseudoatom()
                and not nei.isRSite()
                and nei.atomicNumber() == 1
            ):
                nei.highlight()
                nei.bond().highlight()
    for bond in query.iterateBonds():
        match.mapBond(bond).highlight()
    target.dearomatize()
    return target


def loadMoleculeWithInChI(indigo, indigo_inchi, s, query=False):
    if s.startswith("InChI="):
        m = indigo_inchi.loadMolecule(s.strip())
        m = m if not query else indigo.loadQueryMolecule(m.molfile())
    else:
        m = (
            indigo.loadMolecule(s)
            if not query
            else indigo.loadQueryMolecule(s)
        )
    return m


def merge_dicts(a, b):
    c = a.copy()
    c.update(b)
    return c


def item_to_sdf_chunk(item):
    buf = item["structure"]
    for it in item["properties"].items():
        buf += "> <{}>\n{}\n\n".format(it[0], it[1])
    buf += "$$$$\n"
    return buf


def api_route(self, *args, **kwargs):
    def wrapper(cls):
        self.add_resource(cls, *args, **kwargs)
        return cls

    return wrapper
