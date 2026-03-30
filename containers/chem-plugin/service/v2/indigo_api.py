#!/usr/bin/env python
# -*- coding: utf-8 -*

import base64
import collections
import json
import logging
import traceback
from functools import wraps
from threading import local

from flask import Blueprint, jsonify, request  # type: ignore
from indigo import Indigo, IndigoException  # type: ignore
from indigo.inchi import IndigoInchi  # type: ignore
from indigo.renderer import IndigoRenderer  # type: ignore
from marshmallow.exceptions import ValidationError  # type: ignore

from .common import config
from .common.util import highlight
from .validation import (
    IndigoAutomapSchema,
    IndigoCalculateSchema,
    IndigoCheckSchema,
    IndigoConvertExplicitHydrogensSchema,
    IndigoRendererSchema,
    IndigoRequestSchema,
)

tls = local()

indigo_api = Blueprint("indigo_api", __name__)
indigo_api.indigo_defaults = {  # type: ignore
    "ignore-stereochemistry-errors": "true",
    "smart-layout": "true",
    "gross-formula-add-rsites": "true",
    "mass-skip-error-on-pseudoatoms": "false",
}
indigo_api_logger = logging.getLogger("indigo")

LOG_MAX_SYMBOLS = 50


def get_shorten_text(intext):
    if len(intext) < LOG_MAX_SYMBOLS:
        return intext
    return "{} ...".format(
        intext.strip().ljust(LOG_MAX_SYMBOLS)[:LOG_MAX_SYMBOLS]
    )


def LOG_DATA(*args):
    format_shorten = []
    format_str = []

    for a in args:
        if config.__dict__["LOG_LEVEL"] == "DEBUG":
            format_str.append(str(a))
        else:
            format_shorten.append(get_shorten_text(str(a)))

    if config.__dict__["LOG_LEVEL"] == "DEBUG":
        indigo_api_logger.debug('"{}"'.format('" "'.join(format_str)))
    else:
        indigo_api_logger.info('"{}"'.format('" "'.join(format_shorten)))


def indigo_init(options={}):
    try:
        tls.indigo = Indigo()
        tls.indigo.inchi = IndigoInchi(tls.indigo)
        tls.indigo.renderer = IndigoRenderer(tls.indigo)
        for option, value in indigo_api.indigo_defaults.items():
            tls.indigo.setOption(option, value)
        for option, value in options.items():
            # TODO: Remove this when Indigo API supports smiles type option
            if option in (
                "smiles",
                "smarts",
                "input-format",
                "output-content-type",
                "monomerLibrary",
                "sequence-type",
                "upc",
                "nac",
            ):
                continue
            tls.indigo.setOption(option, value)
        return tls.indigo
    except Exception as e:
        indigo_api_logger.error("indigo-init: {0}".format(e))
        return None


class MolData:
    is_query = False
    is_rxn = False
    struct = None
    substruct = None


def is_rxn(molstr):
    return (
        ">>" in molstr
        or molstr.startswith("$RXN")
        or "<reactantList>" in molstr
    )


def qmol_to_mol(m, selected, indigo):
    for atom in m.iterateAtoms():
        if not atom.index() in selected:
            atom.resetAtom("C")
    for bond in m.iterateBonds():
        if not (
            bond.source().index() in selected
            or bond.destination().index() in selected
        ):
            m.removeBonds(
                [
                    bond.index(),
                ]
            )
    return indigo.loadMolecule(m.clone().molfile())


class ImplicitHCalcExpection(IndigoException):
    pass


def remove_implicit_h_in_selected_components(m, selected):
    if m.countRSites():
        for index in selected:
            if m.getAtom(index).isRSite():
                raise ImplicitHCalcExpection(
                    b"Cannot calculate properties for RGroups"
                )
    if m.countAttachmentPoints():
        count = m.countAttachmentPoints()
        for order in range(1, count + 1):
            for ap in m.iterateAttachmentPoints(order):
                if ap.index() in selected:
                    raise ImplicitHCalcExpection(
                        b"Cannot calculate properties for RGroups"
                    )
    removed_atoms = set()
    implicit_h_decrease = collections.defaultdict(int)
    for c in m.iterateComponents():
        for a in c.iterateAtoms():
            a_index = a.index()
            if a_index not in selected:
                continue
            for n in a.iterateNeighbors():
                n_index = n.index()
                if n_index not in selected:
                    bond_order = None
                    for bond in m.iterateBonds():
                        if all(
                            i in [a_index, n_index]
                            for i in (
                                bond.source().index(),
                                bond.destination().index(),
                            )
                        ):
                            bond_order = bond.bondOrder()
                            break
                    if bond_order not in (1, 2, 3):
                        raise ImplicitHCalcExpection(
                            b"Cannot calculate implicit hydrogens on single atom with query or aromatic bonds"
                        )
                    implicit_h_decrease[a_index] += bond_order
                    removed_atoms.add(n_index)
    m.removeAtoms(list(removed_atoms))
    for index, value in implicit_h_decrease.items():
        a = m.getAtom(index)
        implicit_h_count = a.countImplicitHydrogens() - value
        if implicit_h_count < 0:
            raise ImplicitHCalcExpection(
                b"Cannot calculate implicit hydrogenes on atom with bad valence"
            )
        a.setImplicitHCount(implicit_h_count)
    return m


def iterate_selected_submolecules(r, selected, indigo):
    atomCounter = 0
    for m in r.iterateMolecules():
        moleculeAtoms = []
        for atom in selected:
            if atomCounter <= atom < atomCounter + m.countAtoms():
                moleculeAtoms.append(atom - atomCounter)
        atomCounter += m.countAtoms()
        if moleculeAtoms:
            if r.dbgInternalType() == "#05: <query reaction>":
                m = qmol_to_mol(m, moleculeAtoms, indigo)
                m = remove_implicit_h_in_selected_components(m, moleculeAtoms)
            yield m.getSubmolecule(moleculeAtoms).clone()


def do_calc(m, func_name, precision):
    try:
        value = getattr(m, func_name)()
    except IndigoException as e:
        value = "calculation error: {0}".format(e.value.split(": ")[-1])
    if isinstance(value, float):
        value = round(value, precision)
    return str(value)


def molecule_calc(m, func_name, precision=None):
    results = []
    has_selection = m.hasSelection()
    for component in m.iterateComponents():
        c = component.clone()
        if not has_selection or c.hasSelection():
            results.append(do_calc(c.clone(), func_name, precision))
    return "; ".join(results)


def reaction_calc(rxn, func_name, precision=None):
    reactants_results = []
    has_selection = rxn.hasSelection()
    if rxn.countReactants() > 0 or rxn.countProducts() > 0:
        for r in rxn.iterateReactants():
            if not has_selection or r.hasSelection():
                reactants_results.append(
                    "[{0}]".format(molecule_calc(r, func_name, precision))
                )
        product_results = []
        for p in rxn.iterateProducts():
            if not has_selection or p.hasSelection():
                product_results.append(
                    "[{0}]".format(molecule_calc(p, func_name, precision))
                )
        return "{0} > {1}".format(
            " + ".join(reactants_results), " + ".join(product_results)
        )
    else:
        results = []
        for m in rxn.iterateMolecules():
            if not has_selection or m.hasSelection():
                results.append(do_calc(m, func_name, precision))
        return "; ".join(results)


def remove_unselected_repeating_units_m(m):
    for ru in m.iterateRepeatingUnits():
        for atom in ru.iterateAtoms():
            if not atom.isSelected():
                ru.remove()
                break


def remove_unselected_repeating_units_r(r):
    for m in r.iterateMolecules():
        remove_unselected_repeating_units_m(m)


def try_load_seq(indigo, md, molstr, library, seq_type):
    try:
        md.struct = indigo.loadSequence(molstr, seq_type, library)
        md.is_rxn = False
        md.is_query = False
        return True
    except IndigoException:
        return False


def try_load_fasta(indigo, md, molstr, library, seq_type):
    try:
        md.struct = indigo.loadFasta(molstr, seq_type, library)
        md.is_rxn = False
        md.is_query = False
        return True
    except IndigoException:
        return False


def try_load_macromol(indigo, md, molstr, library, options):
    sequence_type = options.get("sequence-type")
    if try_load_seq(indigo, md, molstr, library, "PEPTIDE-3-LETTER"):
        return
    if sequence_type is not None:
        if try_load_fasta(indigo, md, molstr, library, sequence_type):
            return
    else:
        if try_load_fasta(indigo, md, molstr, library, "PEPTIDE"):
            return
    if molstr.isupper() or molstr.islower():
        if sequence_type is not None:
            if try_load_seq(indigo, md, molstr, library, sequence_type):
                return
        else:
            if try_load_seq(indigo, md, molstr, library, "PEPTIDE"):
                return
    try:
        md.struct = indigo.loadIdt(molstr, library)
        md.is_rxn = False
        md.is_query = False
        return
    except IndigoException:
        pass
    if sequence_type is not None and try_load_seq(
        indigo, md, molstr, library, sequence_type
    ):
        return
    if try_load_seq(indigo, md, molstr, library, "PEPTIDE"):
        return
    try:
        md.struct = indigo.loadHelm(molstr, library)
        md.is_rxn = False
        md.is_query = False
        return
    except IndigoException:
        pass
    try:
        md.struct = indigo.loadAxoLabs(molstr, library)
        md.is_rxn = False
        md.is_query = False
    except IndigoException:
        raise HttpException(
            "struct data not recognized as molecule, query, reaction or reaction query",
            400,
        )


def load_moldata(
    molstr,
    indigo=None,
    options={},
    query=False,
    mime_type=None,
    selected=None,
    library=None,
    try_document=False,
):
    if not indigo:
        try:
            indigo = indigo_init(options)
        except Exception as e:
            raise HttpException(
                "Problem with Indigo initialization: {0}".format(e), 501
            )
    md = MolData()

    if library is None:
        library = indigo.loadMonomerLibrary('{"root":{}}')

    input_format = mime_type
    if "input-format" in options:
        input_format = options["input-format"]
    if input_format in ("smarts", "chemical/x-daylight-smarts"):
        md.struct = indigo.loadSmarts(molstr)
        md.is_query = True
    elif input_format == "chemical/x-peptide-sequence":
        md.struct = indigo.loadSequence(molstr, "PEPTIDE", library)
        md.is_rxn = False
        md.is_query = False
    elif input_format == "chemical/x-peptide-sequence-3-letter":
        md.struct = indigo.loadSequence(molstr, "PEPTIDE-3-LETTER", library)
        md.is_rxn = False
        md.is_query = False
    elif input_format == "chemical/x-rna-sequence":
        md.struct = indigo.loadSequence(molstr, "RNA", library)
        md.is_rxn = False
        md.is_query = False
    elif input_format == "chemical/x-dna-sequence":
        md.struct = indigo.loadSequence(molstr, "DNA", library)
        md.is_rxn = False
        md.is_query = False
    elif input_format == "chemical/x-peptide-fasta":
        md.struct = indigo.loadFasta(molstr, "PEPTIDE", library)
        md.is_rxn = False
        md.is_query = False
    elif input_format == "chemical/x-rna-fasta":
        md.struct = indigo.loadFasta(molstr, "RNA", library)
        md.is_rxn = False
        md.is_query = False
    elif input_format == "chemical/x-dna-fasta":
        md.struct = indigo.loadFasta(molstr, "DNA", library)
        md.is_rxn = False
        md.is_query = False
    elif input_format == "chemical/x-idt":
        md.struct = indigo.loadIdt(molstr, library)
        md.is_rxn = False
        md.is_query = False
    elif input_format == "chemical/x-helm":
        md.struct = indigo.loadHelm(molstr, library)
        md.is_rxn = False
        md.is_query = False
    elif input_format == "chemical/x-axo-labs":
        md.struct = indigo.loadAxoLabs(molstr, library)
        md.is_rxn = False
        md.is_query = False
    elif input_format in ("monomer-library", "chemical/x-monomer-library"):
        md.struct = indigo.loadMonomerLibrary(molstr)
        md.is_rxn = False
        md.is_query = False
    elif molstr.startswith("InChI"):
        md.struct = indigo.inchi.loadMolecule(molstr)
        md.is_rxn = False
        md.is_query = False
    else:
        if try_document:
            try:
                md.struct = indigo.loadKetDocument(molstr)
                return md
            except IndigoException:
                pass
        try:
            if not query:
                md.struct = indigo.loadMoleculeWithLib(molstr, library)
                md.is_query = False
            else:
                md.struct = indigo.loadQueryMoleculeWithLib(molstr, library)
                md.is_query = True
        except IndigoException:
            try:
                md.struct = indigo.loadQueryMoleculeWithLib(molstr, library)
                md.is_query = True
            except IndigoException:
                md.is_rxn = True
                try:
                    if query:
                        try:
                            md.struct = indigo.loadQueryReactionWithLib(
                                molstr, library
                            )
                            md.is_query = True
                        except IndigoException:
                            md.struct = indigo.loadReactionWithLib(
                                molstr, library
                            )
                            md.is_query = False
                    else:
                        md.struct = indigo.loadReactionWithLib(molstr, library)
                        md.is_query = False
                except IndigoException:
                    try:
                        md.struct = indigo.loadQueryReactionWithLib(
                            molstr, library
                        )
                        md.is_query = True
                    except IndigoException:
                        if library is None:
                            raise HttpException(
                                "struct data not recognized as molecule, query, reaction or reaction query",
                                400,
                            )
                        else:  # has library try to load macromolecule
                            try_load_macromol(
                                indigo, md, molstr, library, options
                            )
    return md


def save_moldata(
    md, output_format=None, options={}, indigo=None, library=None
):
    if output_format in ("monomer-library", "chemical/x-monomer-library"):
        return md.struct.monomerLibrary()
    elif output_format in ("chemical/x-mdl-molfile", "chemical/x-mdl-rxnfile"):
        return md.struct.rxnfile() if md.is_rxn else md.struct.molfile()
    elif output_format == "chemical/x-indigo-ket":
        return md.struct.json()
    elif output_format == "chemical/x-sequence":
        return md.struct.sequence(library)
    elif output_format == "chemical/x-peptide-sequence-3-letter":
        return md.struct.sequence3Letter(library)
    elif output_format == "chemical/x-fasta":
        return md.struct.fasta(library)
    elif output_format == "chemical/x-idt":
        return md.struct.idt(library)
    elif output_format == "chemical/x-helm":
        return md.struct.helm(library)
    elif output_format == "chemical/x-axo-labs":
        return md.struct.axolabs(library)
    elif output_format == "chemical/x-daylight-smiles":
        if options.get("smiles") == "canonical":
            return md.struct.canonicalSmiles()
        else:
            indigo.setOption("smiles-saving-format", "daylight")
            return md.struct.smiles()
    elif output_format == "chemical/x-chemaxon-cxsmiles":
        if options.get("smiles") == "canonical":
            return md.struct.canonicalSmiles()
        else:
            indigo.setOption("smiles-saving-format", "chemaxon")
            return md.struct.smiles()
    elif output_format == "chemical/x-daylight-smarts":
        return md.struct.smarts()
    elif output_format == "chemical/x-cml":
        return md.struct.cml()
    elif output_format == "chemical/x-cdxml":
        return md.struct.cdxml()
    elif output_format == "chemical/x-cdx":
        return md.struct.b64cdx()
    elif output_format == "chemical/x-inchi":
        return indigo.inchi.getInchi(md.struct)
    elif output_format == "chemical/x-inchi-key":
        return indigo.inchi.getInchiKey(indigo.inchi.getInchi(md.struct))
    elif output_format == "chemical/x-inchi-aux":
        res = indigo.inchi.getInchi(md.struct)
        aux = indigo.inchi.getAuxInfo()
        return "{}\n{}".format(res, aux)
    elif output_format == "chemical/x-sdf":
        buffer = indigo.writeBuffer()
        sdfSaver = indigo.createSaver(buffer, "sdf")
        for frag in md.struct.iterateComponents():
            sdfSaver.append(frag.clone())
        sdfSaver.close()
        return buffer.toString()
    elif output_format == "chemical/x-rdf":
        buffer = indigo.writeBuffer()
        rdfSaver = indigo.createSaver(buffer, "rdf")
        for reac in md.struct.iterateReactions():
            rdfSaver.append(reac.clone())
        rdfSaver.close()
        return buffer.toString()
    raise HttpException("Format %s is not supported" % output_format, 400)


class HttpException(Exception):
    def __init__(self, value, code, headers={"Content-Type": "text/plain"}):
        self.value = value
        self.code = code
        self.headers = headers


def get_request_data(request):
    request_data = {}
    if request.content_type == "application/json":
        request_data = json.loads(request.data.decode("utf-8"))
        request_data["json_output"] = True
    else:
        request_data["struct"] = request.data.decode("utf-8")
        request_data["input_format"] = (
            request.headers["Content-Type"]
            if "Content-Type" in request.headers
            else None
        )
        request_data["output_format"] = "chemical/x-mdl-molfile"

        if "Accept" in request.headers and request.headers["Accept"] != "*/*":
            request_data["output_format"] = request.headers["Accept"]

        request_data["json_output"] = False
    return request_data


def get_response(
    md, output_struct_format, json_output, options, indigo, library=None
):
    output_mol = save_moldata(
        md, output_struct_format, options, indigo, library
    )
    LOG_DATA(
        "[RESPONSE]", output_struct_format, options, output_mol.encode("utf-8")
    )

    if json_output or options.get("output-content-type") == "application/json":
        return (
            jsonify(
                {
                    "struct": output_mol,
                    "format": output_struct_format,
                    "original_format": md.struct.getOriginalFormat(),
                }
            ),
            200,
            {"Content-Type": "application/json"},
        )
    else:
        return output_mol, 200, {"Content-Type": output_struct_format}


def get_error_response(value, error_code, json_output=False):
    if json_output:
        return (
            jsonify({"error": value}),
            error_code,
            {"Content-Type": "application/json"},
        )
    else:
        return value, error_code, {"Content-Type": "text/plain"}


def check_exceptions(f):
    @wraps(f)
    def wrapper(*args, **kwargs):
        json_output = (
            "Accept" in request.headers
            and request.headers["Accept"] == "application/json"
        ) or (
            "Content-Type" in request.headers
            and request.headers["Content-Type"] == "application/json"
        )
        try:
            return f(*args, **kwargs)
        except ValidationError as e:
            indigo_api_logger.error(
                "[RESPONSE-400] validation error: {0}".format(e.messages)
            )
            indigo_api_logger.debug(traceback.format_exc())
            if json_output:
                return (
                    jsonify({"error": e.messages}),
                    400,
                    {"Content-Type": "application/json; charset=utf-8"},
                )
            else:
                return (
                    "ValidationError: {0}".format(str(e.messages)),
                    400,
                    {"Content-Type": "text/plain"},
                )
        except HttpException as e:
            indigo_api_logger.error("HttpException: {0}".format(e.value))
            indigo_api_logger.debug(traceback.format_exc())
            if json_output:
                return (
                    jsonify({"error": e.value}),
                    e.code,
                    e.headers.update({"Content-Type": "application/json"}),
                )
            else:
                return (
                    e.value,
                    e.code,
                    e.headers.update({"Content-Type": "text/plain"}),
                )
        except IndigoException as e:
            indigo_api_logger.error("IndigoException: {0}".format(e.value))
            indigo_api_logger.error(traceback.format_exc())
            if json_output:
                return (
                    jsonify({"error": "IndigoException: {0}".format(e.value)}),
                    400,
                    {"Content-Type": "application/json"},
                )
            else:
                return (
                    "IndigoException: {0}".format(e.value),
                    400,
                    {"Content-Type": "text/plain"},
                )
        except Exception as e:
            indigo_api_logger.error("Exception: {0}".format(e), exc_info=e)
            indigo_api_logger.debug(traceback.format_exc())
            if json_output:
                return (
                    jsonify({"error": "{0}".format(e)}),
                    500,
                    {"Content-Type": "application/json"},
                )
            else:
                return (
                    "Exception: {0}".format(e),
                    500,
                    {"Content-Type": "text/plain"},
                )

    return wrapper


@indigo_api.route("/info")
@check_exceptions
def info():
    """
    Get information about Indigo version
    ---
    tags:
      - indigo
    responses:
      200:
        description: JSON with Indigo version
    """
    indigo_api_logger.info("[REQUEST] /info")
    indigo = indigo_init()
    return (
        jsonify({"Indigo": {"version": indigo.version()}}),
        200,
        {"Content-Type": "application/json"},
    )


def versionInfo():
    """
    Get information about Indigo version info
    ---
    tags:
      - indigo
    responses:
      200:
        description: JSON with Indigo version
    """
    indigo_api_logger.info("[REQUEST] /info")
    indigo = indigo_init()
    return (
        jsonify({"Indigo": {"version_info": indigo.versionInfo()}}),
        200,
        {"Content-Type": "application/json"},
    )


@indigo_api.route("/aromatize", methods=["POST"])
@check_exceptions
def aromatize():
    """
    Aromatize structure
    ---
    tags:
      - indigo
    parameters:
      - name: json_request
        in: body
        required: true
        schema:
          id: IndigoRequest
          required:
            - struct
          properties:
            struct:
              type: string
              required: true
              examples: C1=CC=CC=C1
            output_format:
              type: string
              default: chemical/x-mdl-molfile
              examples: chemical/x-daylight-smiles
              enum:
                - chemical/x-mdl-rxnfile
                - chemical/x-mdl-molfile
                - chemical/x-indigo-ket
                - chemical/x-daylight-smiles
                - chemical/x-chemaxon-cxsmiles
                - chemical/x-cml
                - chemical/x-inchi
                - chemical/x-iupac
                - chemical/x-daylight-smarts
                - chemical/x-inchi-aux
          example:
            struct: C1=CC=CC=C1
            output_format: chemical/x-daylight-smiles
    responses:
      200:
        description: Aromatized chemical structure
        schema:
          id: IndigoResponse
          required:
            - struct
            - format
          properties:
            struct:
              type: string
            format:
              type: string
              default: chemical/x-mdl-molfile
      400:
        description: 'A problem with supplied client data'
        schema:
          id: ClientError
          required:
            - error
          properties:
            error:
              type: string
      500:
        description: 'A problem on server side'
        schema:
          id: ServerError
          required:
            - error
          properties:
            error:
              type: string
    """

    request_data = get_request_data(request)
    indigo_api_logger.info("[RAW REQUEST] {}".format(request_data))

    data = IndigoRequestSchema().load(request_data)

    LOG_DATA(
        "[REQUEST] /aromatize",
        data["input_format"],
        data["output_format"],
        data["struct"],
        data["options"],
    )
    indigo = indigo_init(data["options"])

    md = load_moldata(
        data["struct"],
        mime_type=data["input_format"],
        options=data["options"],
        indigo=indigo,
    )

    md.struct.aromatize()
    return get_response(
        md,
        data["output_format"],
        data["json_output"],
        data["options"],
        indigo=indigo,
    )


@indigo_api.route("/dearomatize", methods=["POST"])
@check_exceptions
def dearomatize():
    """
    Dearomatize structure
    ---
    tags:
      - indigo
    parameters:
      - name: json_request
        in: body
        required: true
        schema:
          id: IndigoDearomatizeRequest
          properties:
            struct:
              type: string
              required: true
              examples: c1ccccc1
            output_format:
              type: string
              default: chemical/x-mdl-molfile
              examples: chemical/x-daylight-smiles
              enum:
                - chemical/x-mdl-rxnfile
                - chemical/x-mdl-molfile
                - chemical/x-indigo-ket
                - chemical/x-daylight-smiles
                - chemical/x-chemaxon-cxsmiles
                - chemical/x-cml
                - chemical/x-inchi
                - chemical/x-iupac
                - chemical/x-daylight-smarts
                - chemical/x-inchi-aux
          example:
            struct: c1ccccc1
            output_format: chemical/x-daylight-smiles
    responses:
      200:
        description: Dearomatized chemical structure
        schema:
          $ref: "#/definitions/IndigoResponse"
      400:
        description: 'A problem with supplied client data'
        schema:
          $ref: "#/definitions/ClientError"
      500:
        description: 'A problem on server side'
        schema:
          $ref: "#/definitions/ServerError"
    """
    data = IndigoRequestSchema().load(get_request_data(request))

    LOG_DATA(
        "[REQUEST] /dearomatize",
        data["input_format"],
        data["output_format"],
        data["struct"],
        data["options"],
    )
    indigo = indigo_init(data["options"])

    md = load_moldata(
        data["struct"],
        mime_type=data["input_format"],
        options=data["options"],
        indigo=indigo,
    )

    md.struct.dearomatize()
    return get_response(
        md,
        data["output_format"],
        data["json_output"],
        data["options"],
        indigo=indigo,
    )


@indigo_api.route("/convert", methods=["POST"])
@check_exceptions
def convert():
    """
    Convert structure to Molfile/Rxnfile, SMILES, CML or InChI
    ---
    tags:
      - indigo
    parameters:
      - name: json_request
        in: body
        required: true
        schema:
          id: IndigoConvertRequest
          properties:
            struct:
              type: string
              required: true
              examples: C1=CC=CC=C1
            output_format:
              type: string
              default: chemical/x-mdl-molfile
              enum:
                - chemical/x-mdl-rxnfile
                - chemical/x-mdl-molfile
                - chemical/x-indigo-ket
                - chemical/x-daylight-smiles
                - chemical/x-chemaxon-cxsmiles
                - chemical/x-cml
                - chemical/x-inchi
                - chemical/x-inchi-key
                - chemical/x-iupac
                - chemical/x-daylight-smarts
                - chemical/x-inchi-aux
                - chemical/x-monomer-library
          example:
            struct: C1=CC=CC=C1
            output_format: chemical/x-mdl-molfile
    responses:
      200:
        description: Chemical structure in requested format
        schema:
          $ref: "#/definitions/IndigoResponse"
      400:
        description: 'A problem with supplied client data'
        schema:
          $ref: "#/definitions/ClientError"
      500:
        description: 'A problem on server side'
        schema:
          $ref: "#/definitions/ServerError"
    """
    if request.method == "POST":
        data = IndigoRequestSchema().load(get_request_data(request))

        LOG_DATA(
            "[REQUEST] /convert",
            data["input_format"],
            data["output_format"],
            data["struct"].encode("utf-8"),
            data["options"],
        )
        indigo = indigo_init(data["options"])

        monomer_library = data["options"].get("monomerLibrary")
        library = None
        if monomer_library is not None:
            library = indigo.loadMonomerLibrary(monomer_library)
        else:
            library = indigo.loadMonomerLibrary('{"root":{}}')

        query = False
        if "smarts" in data["output_format"]:
            query = True

        try_document = False
        if data["output_format"] in (
            "chemical/x-sequence",
            "chemical/x-fasta",
            "chemical/x-idt",
            "chemical/x-helm",
            "chemical/x-peptide-sequence-3-letter",
            "chemical/x-axo-labs",
        ):
            try_document = True

        md = load_moldata(
            data["struct"],
            mime_type=data["input_format"],
            options=data["options"],
            indigo=indigo,
            query=query,
            library=library,
            try_document=try_document,
        )
        return get_response(
            md,
            data["output_format"],
            data["json_output"],
            data["options"],
            indigo=indigo,
            library=library,
        )
    elif request.method == "GET":
        input_dict = {
            "struct": request.args["struct"],
            "output_format": (
                request.args["output_format"]
                if "output_format" in request.args
                else "chemical/x-mdl-molfile"
            ),
        }

        data = IndigoRequestSchema().load(input_dict)

        LOG_DATA(
            "[REQUEST] /convert",
            data["input_format"],
            data["output_format"],
            data["struct"].encode("utf-8"),
            data["options"],
        )
        indigo = indigo_init(data["options"])

        monomer_library = data["options"].get("monomerLibrary")
        library = None
        if monomer_library is not None:
            library = indigo.loadMonomerLibrary(monomer_library)
        else:
            library = indigo.loadMonomerLibrary('{"root":{}}')

        md = load_moldata(
            data["struct"],
            mime_type=data["input_format"],
            options=data["options"],
            indigo=indigo,
            library=library,
            try_document=True,
        )

        if "json_output" in request.args:
            data["json_output"] = True
        else:
            data["json_output"] = False

        return get_response(
            md,
            data["output_format"],
            data["json_output"],
            data["options"],
            indigo=indigo,
            library=library,
        )


@indigo_api.route("/convert_explicit_hydrogens", methods=["POST"])
@check_exceptions
def convert_explicit_hydrogens():
    """
    Convert hydrogens from implicit to explicit and vice versa
    ---
    tags:
      - indigo
    parameters:
      - name: json_request
        in: body
        required: true
        schema:
          id: IndigoConvertExplicitHydrogensRequest
          properties:
            struct:
              type: string
              required: true
              examples: C1=CC=CC=C1
            mode:
              type: string
              default: auto
              enum:
                auto
                fold
                unfold
            output_format:
              type: string
              default: chemical/x-mdl-molfile
              enum:
                - chemical/x-mdl-rxnfile
                - chemical/x-mdl-molfile
                - chemical/x-indigo-ket
                - chemical/x-daylight-smiles
                - chemical/x-chemaxon-cxsmiles
                - chemical/x-cml
                - chemical/x-inchi
                - chemical/x-iupac
                - chemical/x-daylight-smarts
                - chemical/x-inchi-aux
          example:
            struct: C1=CC=CC=C1
            output_format: chemical/x-mdl-molfile
    responses:
      200:
        description: Chemical structure with converted explicit hydrogens
        schema:
          $ref: "#/definitions/IndigoResponse"
      400:
        description: 'A problem with supplied client data'
        schema:
          $ref: "#/definitions/ClientError"
      500:
        description: 'A problem on server side'
        schema:
          $ref: "#/definitions/ServerError"
    """
    data = IndigoConvertExplicitHydrogensSchema().load(
        get_request_data(request)
    )

    LOG_DATA(
        "[REQUEST] /convert_explicit_hydrogens",
        data["input_format"],
        data["output_format"],
        data["struct"].encode("utf-8"),
        data.get("mode", "mode undefined"),
    )
    indigo = indigo_init(data["options"])
    query = False
    if "smarts" in data["output_format"]:
        query = True
    md = load_moldata(
        data["struct"],
        mime_type=data["input_format"],
        options=data["options"],
        indigo=indigo,
        query=query,
    )
    mode = data.get("mode", "auto")
    if mode == "fold":
        md.struct.foldHydrogens()
    elif mode == "unfold":
        md.struct.unfoldHydrogens()
    else:
        md.struct.foldUnfoldHydrogens()
    return get_response(
        md,
        data["output_format"],
        data["json_output"],
        data["options"],
        indigo=indigo,
    )


@indigo_api.route("/layout", methods=["POST"])
@check_exceptions
def layout():
    """
    Layout structure
    ---
    tags:
      - indigo
    parameters:
      - name: json_request
        in: body
        required: true
        schema:
          id: IndigoLayoutRequest
          properties:
            struct:
              type: string
              required: true
              examples: C1=CC=CC=C1
            output_format:
              type: string
              default: chemical/x-mdl-molfile
              enum:
                - chemical/x-mdl-rxnfile
                - chemical/x-mdl-molfile
                - chemical/x-indigo-ket
                - chemical/x-daylight-smiles
                - chemical/x-chemaxon-cxsmiles
                - chemical/x-cml
                - chemical/x-inchi
                - chemical/x-iupac
                - chemical/x-daylight-smarts
                - chemical/x-inchi-aux
          example:
            struct: C1=CC=CC=C1
            output_format: chemical/x-mdl-molfile
    responses:
      200:
        description: Chemical structure with calculated correct coordinates
        schema:
          $ref: "#/definitions/IndigoResponse"
      400:
        description: 'A problem with supplied client data'
        schema:
          $ref: "#/definitions/ClientError"
      500:
        description: 'A problem on server side'
        schema:
          $ref: "#/definitions/ServerError"
    """
    data = IndigoRequestSchema().load(get_request_data(request))
    LOG_DATA(
        "[REQUEST] /layout",
        data["input_format"],
        data["output_format"],
        data["struct"],
        data["options"],
    )
    indigo = indigo_init(data["options"])
    query = False
    if "smarts" in data["output_format"]:
        query = True
    md = load_moldata(
        data["struct"],
        mime_type=data["input_format"],
        options=data["options"],
        indigo=indigo,
        query=query,
    )
    md.struct.layout()
    return get_response(
        md,
        data["output_format"],
        data["json_output"],
        data["options"],
        indigo=indigo,
    )


@indigo_api.route("/clean", methods=["POST"])
@check_exceptions
def clean():
    """
    Clean up structure or selected substructure coordinates
    ---
    tags:
      - indigo
    parameters:
      - name: json_request
        in: body
        required: true
        schema:
          id: IndigoClean2dRequest
          properties:
            struct:
              type: string
              required: true
              examples: C1=CC=CC=C1
            output_format:
              type: string
              default: chemical/x-mdl-molfile
              enum:
                - chemical/x-mdl-rxnfile
                - chemical/x-mdl-molfile
                - chemical/x-indigo-ket
                - chemical/x-daylight-smiles
                - chemical/x-chemaxon-cxsmiles
                - chemical/x-cml
                - chemical/x-inchi
                - chemical/x-iupac
                - chemical/x-daylight-smarts
                - chemical/x-inchi-aux
          example:
            struct: C1=CC=CC=C1
            output_format: chemical/x-mdl-molfile
    responses:
      200:
        description: Chemical structure with approximately corrected coordinates
        schema:
          $ref: "#/definitions/IndigoResponse"
      400:
        description: 'A problem with supplied client data'
        schema:
          $ref: "#/definitions/ClientError"
      500:
        description: 'A problem on server side'
        schema:
          $ref: "#/definitions/ServerError"
    """
    data = IndigoRequestSchema().load(get_request_data(request))
    LOG_DATA(
        "[REQUEST] /clean",
        data["input_format"],
        data["output_format"],
        data["struct"],
        data["options"],
    )
    indigo = indigo_init(data["options"])

    md = load_moldata(
        data["struct"],
        mime_type=data["input_format"],
        options=data["options"],
        selected=data["selected"],
        indigo=indigo,
    )
    if md.is_rxn and data["selected"]:
        for sm in iterate_selected_submolecules(md.struct, data["selected"]):
            sm.clean2d()
    else:
        md.substruct = (
            md.struct.getSubmolecule(data["selected"])
            if data["selected"]
            else md.struct
        )
        md.substruct.clean2d()
    return get_response(
        md,
        data["output_format"],
        data["json_output"],
        data["options"],
        indigo=indigo,
    )


@indigo_api.route("/automap", methods=["POST"])
@check_exceptions
def automap():
    """
    Automatically calculate reaction atoms mapping
    ---
    tags:
      - indigo
    parameters:
      - name: json_request
        in: body
        required: true
        schema:
          id: IndigoAutomapRequest
          properties:
            struct:
              type: string
              required: true
              examples: C1=CC=CC=C1.N>>C1=CC=CC=N1.C
            output_format:
              type: string
              default: chemical/x-mdl-rxnfile
              enum:
                - chemical/x-mdl-rxnfile
                - chemical/x-mdl-molfile
                - chemical/x-indigo-ket
                - chemical/x-daylight-smiles
                - chemical/x-chemaxon-cxsmiles
                - chemical/x-cml
                - chemical/x-inchi
                - chemical/x-iupac
                - chemical/x-daylight-smarts
                - chemical/x-inchi-aux
          example:
            struct: C1=CC=CC=C1.N>>C1=CC=CC=N1.C
            output_format: chemical/x-mdl-rxnfile
    responses:
      200:
        description: Reaction with calculated atom-to-atom mappings
        schema:
          $ref: "#/definitions/IndigoResponse"
      400:
        description: 'A problem with supplied client data'
        schema:
          $ref: "#/definitions/ClientError"
      500:
        description: 'A problem on server side'
        schema:
          $ref: "#/definitions/ServerError"
    """
    data = IndigoAutomapSchema().load(get_request_data(request))
    LOG_DATA(
        "[REQUEST] /automap",
        data["input_format"],
        data["output_format"],
        data["mode"],
        data["struct"],
        data["options"],
    )
    indigo = indigo_init(data["options"])
    md = load_moldata(
        data["struct"],
        mime_type=data["input_format"],
        options=data["options"],
        indigo=indigo,
    )
    md.struct.automap(data["mode"])
    return get_response(
        md,
        data["output_format"],
        data["json_output"],
        data["options"],
        indigo=indigo,
    )


@indigo_api.route("/calculate_cip", methods=["POST"])
@check_exceptions
def calculate_cip():
    """
    Calculate CIP
    ---
    tags:
      - indigo
    parameters:
      - name: json_request
        in: body
        required: true
        schema:
          id: IndigoCalculateCipRequest
          properties:
            struct:
              type: string
              required: true
              examples: C1=CC=CC=C1
            output_format:
              type: string
              default: chemical/x-mdl-molfile
              enum:
                - chemical/x-mdl-rxnfile
                - chemical/x-mdl-molfile
                - chemical/x-indigo-ket
                - chemical/x-daylight-smiles
                - chemical/x-chemaxon-cxsmiles
                - chemical/x-cml
                - chemical/x-inchi
                - chemical/x-iupac
                - chemical/x-daylight-smarts
                - chemical/x-inchi-aux
          example:
            struct: C1=CC=CC=C1
            output_format: chemical/x-mdl-molfile
    responses:
      200:
        description: Chemical structure with calculaated CIP
        schema:
          $ref: "#/definitions/IndigoResponse"
      400:
        description: 'A problem with supplied client data'
        schema:
          $ref: "#/definitions/ClientError"
      500:
        description: 'A problem on server side'
        schema:
          $ref: "#/definitions/ServerError"
    """
    data = IndigoRequestSchema().load(get_request_data(request))

    LOG_DATA(
        "[REQUEST] /calculate_cip",
        data["input_format"],
        data["output_format"],
        data["struct"],
        data["options"],
    )
    indigo = indigo_init(data["options"])
    md = load_moldata(
        data["struct"],
        mime_type=data["input_format"],
        options=data["options"],
        indigo=indigo,
    )
    indigo.setOption("json-saving-add-stereo-desc", True)
    indigo.setOption("molfile-saving-add-stereo-desc", True)
    return get_response(
        md,
        data["output_format"],
        data["json_output"],
        data["options"],
        indigo=indigo,
    )


@indigo_api.route(
    "/check",
    methods=[
        "POST",
    ],
)
@check_exceptions
def check():
    """
    Check chemical structure
    ---
    tags:
      - indigo
    parameters:
      - name: json_request
        in: body
        required: true
        schema:
          id: IndigoCheckRequest
          properties:
            struct:
              type: string
              required: true
              examples: C1=CC=CC=C1
            types:
              type: array
              default: ["valence", "ambiguous_h", "query", "pseudoatoms", "radicals", "stereo", "overlapping_atoms", "overlapping_bonds", "3d", "sgroups", "v3000", "rgroups"]
              enum:
                - valence
                - ambiguous_h
                - query
                - pseudoatoms
                - radicals
                - stereo
                - overlapping_atoms
                - overlapping_bonds
                - 3d
                - sgroups
                - v3000
                - rgroups
          example:
            struct: "[C+5]"
            types: ["valence", "ambiguous_h"]
    responses:
      200:
        description: JSON with errors for given types if errors present
        schema:
          id: IndigoCheckResponse
      400:
        description: 'A problem with supplied client data'
        schema:
          $ref: "#/definitions/ClientError"
      500:
        description: 'A problem on server side'
        schema:
          $ref: "#/definitions/ServerError"
    """
    data = IndigoCheckSchema().load(get_request_data(request))
    try:
        indigo = indigo_init(data["options"])
    except Exception as e:
        raise HttpException(
            "Problem with Indigo initialization: {0}".format(e), 501
        )
    LOG_DATA(
        "[REQUEST] /check", data["types"], data["struct"], data["options"]
    )
    result = indigo.check(data["struct"], json.dumps(data["types"]))
    return result, 200, {"Content-Type": "application/json"}


@indigo_api.route(
    "/calculate",
    methods=[
        "POST",
    ],
)
@check_exceptions
def calculate():
    """
    Calculate properites for input structure
    ---
    tags:
    - indigo
    parameters:
    - name: json_request
      in: body
      required: true
      schema:
        id: IndigoCalculateRequest
        properties:
          struct:
            type: string
            required: true
            examples: C1=CC=CC=C1
          properties:
            type: array
            default: ["molecular-weight"]
            examples: ["molecular-weight"]
            enum:
             - molecular-weight
             - most-abundant-mass
             - monoisotopic-mass
             - gross
             - mass-composition
        example:
          struct: C1=CC=CC=C1
          properties: ["molecular-weight"]
    responses:
      200:
        description: Calculated properties
      400:
        description: 'A problem with supplied client data'
        schema:
          $ref: "#/definitions/ClientError"
      500:
        description: 'A problem on server side'
        schema:
          $ref: "#/definitions/ServerError"
    """
    data = IndigoCalculateSchema().load(get_request_data(request))
    LOG_DATA(
        "[REQUEST] /calculate",
        data["properties"],
        data["selected"],
        data["struct"],
        data["options"],
    )
    indigo = indigo_init(data["options"])
    md = load_moldata(
        data["struct"],
        mime_type=data["input_format"],
        options=data["options"],
        selected=data["selected"],
        indigo=indigo,
    )
    if md.struct.hasSelection():
        if md.is_rxn:
            remove_unselected_repeating_units_r(md.struct)
        else:
            remove_unselected_repeating_units_m(md.struct)
    calculate_properties = data["properties"]
    result = {}
    precision = data["precision"]
    func_name_dict = {
        "molecular-weight": "molecularWeight",
        "most-abundant-mass": "mostAbundantMass",
        "monoisotopic-mass": "monoisotopicMass",
        "mass-composition": "massComposition",
        "gross": "grossFormula",
    }
    for p in calculate_properties:
        if md.is_rxn:
            result[p] = reaction_calc(
                md.struct, func_name_dict[p], precision=precision
            )
        else:
            result[p] = molecule_calc(
                md.struct, func_name_dict[p], precision=precision
            )
    if data["json_output"]:
        return jsonify(result), 200, {"Content-Type": "application/json"}
    else:
        return (
            "\n".join(
                [
                    "{0}: {1}".format(key, value)
                    for key, value in {
                        k: v for k, v in result.items() if v
                    }.items()
                ]
            ),
            200,
            {"Content-Type": "text/plain"},
        )


@indigo_api.route("/render", methods=["POST"])
@check_exceptions
def render():
    """
    Render molecule
    ---
    tags:
      - indigo
    description: 'Returns a molecule image'
    parameters:
      - name: json_request
        in: body
        required: true
        schema:
          id: IndigoRenderRequest
          properties:
            struct:
              type: string
              examples: C1=CC=CC=C1
            query:
              type: string
              examples: C
            output_format:
              type: string
              default: image/svg+xml
            options:
              type: array
          example:
            struct: C1=CC=CC=C1
            query: C
            output_format: image/svg+xml
    responses:
      200:
        description: 'A rendered molecule image'
        schema:
          type: file
      400:
        description: 'A problem with supplied client data'
        schema:
          $ref: "#/definitions/ClientError"
      500:
        description: 'A problem on server side'
        schema:
          $ref: "#/definitions/ServerError"
    """
    render_format_dict = {
        "image/svg+xml": "svg",
        "image/png": "png",
        "application/pdf": "pdf",
        "image/png;base64": "png",
        "image/svg;base64": "svg",
    }

    render_format_dict_r = {
        "png": "image/png;base64",
        "svg": "image/svg;base64",
        "pdf": "application/pdf;base64",
    }
    # if request.method == "POST":
    LOG_DATA(
        "[REQUEST] /render",
        request.headers["Content-Type"],
        request.headers["Accept"],
        request.data,
    )
    try:
        if "application/json" in request.headers["Content-Type"]:
            input_dict = json.loads(request.data.decode())
        else:
            input_dict = {
                "struct": request.data,
                "output_format": request.headers["Accept"],
            }
    except ValueError:
        return get_error_response(
            "Invalid input JSON: {0}".format(request.data), 400
        )

    data = IndigoRendererSchema().load(input_dict)
    indigo = indigo_init(data["options"])
    if data["struct"] and not data["query"]:
        md = load_moldata(
            data["struct"],
            mime_type=data["input_format"],
            options=data["options"],
            indigo=indigo,
        )
    elif data["query"] and not data["struct"]:
        md = load_moldata(
            data["query"], options=data["options"], indigo=indigo
        )
    else:
        md = load_moldata(
            data["struct"],
            mime_type=data["input_format"],
            options=data["options"],
            indigo=indigo,
        )
        mdq = load_moldata(
            data["query"],
            mime_type=data["input_format"],
            query=True,
            indigo=indigo,
        )
        try:
            md.struct = highlight(indigo, md.struct, mdq.struct)
        except RuntimeError:
            pass

    # elif request.method == "GET":

    #     LOG_DATA("[REQUEST] /render GET", request.args)

    #     try:
    #         input_dict = {
    #             "struct": request.args["struct"]
    #             if "struct" in request.args
    #             else None,
    #             "output_format": request.args["output_format"]
    #             if "output_format" in request.args
    #             else "image/svg+xml",
    #             "query": request.args["query"]
    #             if "query" in request.args
    #             else None,
    #         }
    #         if input_dict["struct"] and not input_dict["query"]:
    #             md = load_moldata(input_dict["struct"])
    #         elif input_dict["query"] and not input_dict["struct"]:
    #             mdq = load_moldata(input_dict["query"])
    #         else:
    #             md = load_moldata(input_dict["struct"])
    #             mdq = load_moldata(
    #                 input_dict["query"],
    #                 indigo=md.struct._session,
    #                 query=True,
    #             )
    #             md.struct = highlight(
    #                 md.struct._session, md.struct, mdq.struct
    #             )
    #         data = IndigoRendererSchema().load(input_dict)
    #     except Exception as e:
    #         return get_error_response(
    #             "Invalid GET query {}".format(str(e)), 400
    #         )

    # indigo = md.struct._session
    # indigo = indigo_init(data["options"])
    content_type = data["output_format"]
    if "render-output-format" in data["options"]:
        rof = data["options"]["render-output-format"]
        content_type = render_format_dict_r[rof]
    else:
        indigo.setOption(
            "render-output-format", render_format_dict[content_type]
        )

    result = indigo.renderer.renderToBuffer(md.struct)

    if "base64" in content_type:
        result = base64.b64encode(result)

    indigo_api_logger.info(
        "[RESPONSE] Content-Type: {0}, Content-Size: {1}".format(
            content_type, len(result)
        )
    )
    return result, 200, {"Content-Type": content_type}


@indigo_api.route("/calculateMacroProperties", methods=["POST"])
@check_exceptions
def calculateMacroProperties():
    """
    Calculate macromulecule properties
    ---
    tags:
      - indigo
    parameters:
      - name: json_request
        in: body
        required: true
        schema:
          id: IndigoRequest
          required:
            - struct
          properties:
            struct:
              type: string
              required: true
              examples: C1=CC=CC=C1
            output_format:
              type: string
              default: chemical/x-mdl-molfile
              examples: chemical/x-daylight-smiles
              enum:
                - chemical/x-mdl-rxnfile
                - chemical/x-mdl-molfile
                - chemical/x-indigo-ket
                - chemical/x-daylight-smiles
                - chemical/x-chemaxon-cxsmiles
                - chemical/x-cml
                - chemical/x-inchi
                - chemical/x-iupac
                - chemical/x-daylight-smarts
                - chemical/x-inchi-aux
          example:
            struct: C1=CC=CC=C1
            output_format: chemical/x-daylight-smiles
    responses:
      200:
        description: Aromatized chemical structure
        schema:
          id: IndigoResponse
          required:
            - struct
            - format
          properties:
            struct:
              type: string
            format:
              type: string
              default: chemical/x-mdl-molfile
      400:
        description: 'A problem with supplied client data'
        schema:
          id: ClientError
          required:
            - error
          properties:
            error:
              type: string
      500:
        description: 'A problem on server side'
        schema:
          id: ServerError
          required:
            - error
          properties:
            error:
              type: string
    """

    request_data = get_request_data(request)
    indigo_api_logger.info("[RAW REQUEST] {}".format(request_data))

    data = IndigoRequestSchema().load(request_data)

    LOG_DATA(
        "[REQUEST] /calculateMacroProperties",
        data["input_format"],
        data["output_format"],
        data["struct"],
        data["options"],
    )
    indigo = indigo_init(data["options"])

    md = load_moldata(
        data["struct"],
        mime_type=data["input_format"],
        options=data["options"],
        indigo=indigo,
        try_document=True,
    )

    options = data["options"]
    # UPC is molar concentration of unipositive cations, NAC is molar concentration of the nucleotide strands, these options need to calculate melting temperature
    upc = 0.14  # default value is the average physiological - 140 mM
    if "upc" in options:
        try:
            upc = float(options["upc"])
        except ValueError:
            raise IndigoException("Invalid value for UPC")
    nac = 0
    if "nac" in options:
        try:
            nac = float(options["nac"])
        except ValueError:
            raise IndigoException("Invalid value for NAC")
    else:
        raise IndigoException("NAC option is mandatory")
    result = {"properties": md.struct.macroProperties(upc, nac)}

    return jsonify(result), 200, {"Content-Type": "application/json"}


@indigo_api.route("/expand", methods=["POST"])
@check_exceptions
def expand():
    """
    Expand selected monomers
    ---
    tags:
      - indigo
    parameters:
      - name: json_request
        in: body
        required: true
        schema:
          id: IndigoRequest
          required:
            - struct
          properties:
            struct:
              type: string
              required: true
              examples: C1=CC=CC=C1
            output_format:
              type: string
              default: chemical/x-mdl-molfile
              examples: chemical/x-daylight-smiles
              enum:
                - chemical/x-mdl-rxnfile
                - chemical/x-mdl-molfile
                - chemical/x-indigo-ket
                - chemical/x-daylight-smiles
                - chemical/x-chemaxon-cxsmiles
                - chemical/x-cml
                - chemical/x-inchi
                - chemical/x-iupac
                - chemical/x-daylight-smarts
                - chemical/x-inchi-aux
          example:
            struct: C1=CC=CC=C1
            output_format: chemical/x-daylight-smiles
    responses:
      200:
        description: structure with selected monomers expanded
        schema:
          id: IndigoResponse
          required:
            - struct
            - format
          properties:
            struct:
              type: string
            format:
              type: string
              default: chemical/x-mdl-molfile
      400:
        description: 'A problem with supplied client data'
        schema:
          id: ClientError
          required:
            - error
          properties:
            error:
              type: string
      500:
        description: 'A problem on server side'
        schema:
          id: ServerError
          required:
            - error
          properties:
            error:
              type: string
    """

    request_data = get_request_data(request)
    indigo_api_logger.info("[RAW REQUEST] {}".format(request_data))

    data = IndigoRequestSchema().load(request_data)

    LOG_DATA(
        "[REQUEST] /expand",
        data["input_format"],
        data["output_format"],
        data["struct"],
        data["options"],
    )
    indigo = indigo_init(data["options"])

    monomer_library = data["options"].get("monomerLibrary")
    library = None
    if monomer_library is not None:
        library = indigo.loadMonomerLibrary(monomer_library)
    else:
        library = indigo.loadMonomerLibrary('{"root":{}}')

    md = load_moldata(
        data["struct"],
        mime_type=data["input_format"],
        options=data["options"],
        indigo=indigo,
        library=library,
        try_document=True,
    )

    md.struct.expandMonomers()

    return get_response(
        md,
        data["output_format"],
        data["json_output"],
        data["options"],
        indigo=indigo,
    )
