from marshmallow import Schema, fields  # type: ignore
from marshmallow.decorators import (  # type: ignore
    post_load,
    validates,
    validates_schema,
)
from marshmallow.exceptions import ValidationError  # type: ignore
from marshmallow.validate import OneOf  # type: ignore


class InputFormatSchema(Schema):
    struct_mime_types = (
        "chemical/x-mdl-rxnfile",
        "chemical/x-mdl-molfile",
        "chemical/x-indigo-ket",
        "chemical/x-daylight-smiles",
        "chemical/x-cml",
        "chemical/x-inchi",
        "chemical/x-inchi-key",
        "chemical/x-iupac",
        "chemical/x-daylight-smarts",
        "chemical/x-inchi-aux",
        "chemical/x-chemaxon-cxsmiles",
        "chemical/x-cdxml",
        "chemical/x-cdx",
        "chemical/x-sdf",
        "chemical/x-rdf",
        "chemical/x-peptide-sequence",
        "chemical/x-peptide-sequence-3-letter",
        "chemical/x-rna-sequence",
        "chemical/x-dna-sequence",
        "chemical/x-sequence",
        "chemical/x-peptide-fasta",
        "chemical/x-rna-fasta",
        "chemical/x-dna-fasta",
        "chemical/x-fasta",
        "chemical/x-idt",
        "chemical/x-helm",
        "chemical/x-monomer-library",
        "chemical/x-axo-labs",
    )
    input_format = fields.Str(missing=None, validate=OneOf(struct_mime_types))


# TODO: Move structure loading to validation phase
class IndigoRendererSchema(InputFormatSchema):
    images_formats = (
        "image/svg+xml",
        "image/png",
        "application/pdf",
        "image/png;base64",
        "image/svg;base64",
    )
    output_format = fields.Str(
        missing="image/svg+xml", validate=OneOf(images_formats)
    )
    struct = fields.Str(missing=None)
    query = fields.Str(missing=None)
    options = fields.Dict(missing={})

    @validates_schema
    def structure_or_query_exists(self, data, **kwargs):
        if not data["struct"] and not data["query"]:
            raise ValidationError(
                "No query or structure parameter in client request."
            )


class IndigoBaseSchema(InputFormatSchema):
    struct = fields.Str()
    options = fields.Dict(missing={})
    json_output = fields.Bool()
    selected = fields.List(fields.Integer, missing=[])

    @validates_schema
    def check_struct(self, data, **kwargs):
        if not data["struct"]:
            raise ValidationError("Empty structure")


class IndigoRequestSchema(IndigoBaseSchema):
    output_format = fields.Str(
        missing="chemical/x-mdl-molfile",
        validate=OneOf(IndigoBaseSchema.struct_mime_types),
    )

    @staticmethod
    def is_rxn(molstr):
        return (
            ">>" in molstr
            or molstr.startswith("$RXN")
            or "<reactantList>" in molstr
        )

    @validates_schema
    def check_struct_rxnfile(self, data, **kwargs):
        if (
            "output_format" in data
            and data["output_format"] == "chemical/x-mdl-molfile"
            and self.is_rxn(data["struct"])
        ):
            data["output_format"] = "chemical/x-mdl-rxnfile"


class IndigoCheckSchema(IndigoBaseSchema):
    verify_types = (
        "valence",
        "ambiguous_h",
        "query",
        "pseudoatoms",
        "radicals",
        "stereo",
        "overlapping_atoms",
        "overlapping_bonds",
        "3d",
        "sgroups",
        "v3000",
        "rgroups",
        "chiral",
    )
    types = fields.List(
        fields.Str,
        missing=[
            "valence",
            "ambiguous_h",
            "query",
            "pseudoatoms",
            "radicals",
            "stereo",
            "overlapping_atoms",
            "overlapping_bonds",
            "3d",
            "sgroups",
            "v3000",
            "rgroups",
        ],
    )

    @validates_schema
    def check_types(self, data, **kwargs):
        if "types" in data:
            for t in data["types"]:
                if t not in IndigoCheckSchema.verify_types:
                    raise ValidationError(
                        "Wrong check value: {0}, should be one of {1}".format(
                            t, IndigoCheckSchema.verify_types
                        )
                    )


class IndigoCalculateSchema(IndigoBaseSchema):
    calculate_properties = (
        "molecular-weight",
        "most-abundant-mass",
        "monoisotopic-mass",
        "gross",
        "mass-composition",
    )
    properties = fields.List(fields.Str, missing=["molecular-weight"])
    precision = fields.Int(missing=7)

    @validates_schema
    def check_properties(self, data, **kwargs):
        if "properties" in data:
            for p in data["properties"]:
                if p not in IndigoCalculateSchema.calculate_properties:
                    raise ValidationError(
                        "Wrong property value: {0}, should be one of {1}".format(
                            p, IndigoCalculateSchema.calculate_properties
                        )
                    )


class IndigoAutomapSchema(IndigoRequestSchema):
    output_format = fields.Str(
        missing="chemical/x-mdl-rxnfile",
        validate=OneOf(IndigoBaseSchema.struct_mime_types),
    )
    mode = fields.Str(
        missing="discard",
        validate=OneOf(("discard", "alter", "clear", "keep")),
    )


class IndigoConvertExplicitHydrogensSchema(IndigoRequestSchema):
    mode = fields.Str(
        missing="auto",
        validate=OneOf(("auto", "fold", "unfold")),
    )


class SearcherSchema(Schema):
    type = fields.Str(
        load_from="type",
        required=True,
        validate=OneOf(["sub", "exact", "sim", "molFormula"]),
    )
    library_ids = fields.List(fields.Str, required=True)
    query_structure = fields.Str(missing="")
    query_text = fields.Str(missing="")
    limit = fields.Integer(missing=10, validate=lambda n: 0 < n <= 100)
    offset = fields.Integer(missing=0)
    min_sim = fields.Number(load_from="min", missing=0)
    max_sim = fields.Number(load_from="max", missing=1)
    metric = fields.Str(missing="")
    options = fields.Str(missing="")

    @post_load
    def strip_text_query(self, data, **kwargs):
        data["query_text"] = data["query_text"].strip()
        return data

    @validates_schema
    def query_exists(self, data, **kwargs):
        if not data["query_structure"] and not data["query_text"]:
            raise ValidationError("Empty queries.")

    @validates_schema
    def type_exists(self, data, **kwargs):
        if "type" not in data:
            raise ValidationError(
                "No search type selected, must be one of: 'sub', 'exact, sim', 'molFormula'"
            )
        if data["type"] not in ("sub", "exact", "sim", "molFormula"):
            raise ValidationError(
                "Wrong search type {0}, must be one of 'sub', 'exact, sim', 'molFormula'".format(
                    data["type"]
                )
            )

    @validates_schema
    def sim_min_max(self, data, **kwargs):
        if (
            data.get("type")
            and "sim" in data.get("type")
            and data.get("min_sim") > data.get("max_sim")
        ):
            raise ValidationError("Similarity min can not be greater than max")

    @validates_schema
    def sim_min_range(self, data, **kwargs):
        if (
            data.get("type")
            and "sim" in data.get("type")
            and (data.get("min_sim") < 0 or data.get("min_sim") >= 1)
        ):
            raise ValidationError(
                "Invalid similarity min range. Should be within [0; 1)"
            )

    @validates_schema
    def sim_max_range(self, data, **kwargs):
        print(data, data.get("type"), data.get("max_sim"), data.get("min_sim"))
        if (
            data.get("type")
            and "sim" in data.get("type")
            and (data.get("max_sim") <= 0 or data.get("max_sim") > 1)
        ):
            raise ValidationError(
                "Invalid similarity max range. Should be within (0; 1]"
            )


class LibrarySchema(Schema):
    name = fields.Str(required=True)
    user_data = fields.Dict(required=True)

    @validates("name")
    def validate_name(self, name):
        if not name.strip():
            raise ValidationError("Library name cannot be empty.")


class UserSchema(Schema):
    username = fields.Str(required=True)
    password = fields.Str(required=True)
    email = fields.Str(required=True)
    foreign_auth_provider = fields.Str(missing=None)
    foreign_auth_id = fields.Integer(missing=None)

    @validates("username")
    def validate_username(self, username):
        if not username.strip():
            raise ValidationError("username cannot be empty.")
