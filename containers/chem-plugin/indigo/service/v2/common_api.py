# This file was modified for eLabFTW
# Apache 2.0 LICENSE
import logging

from flask import Blueprint, jsonify  # type: ignore

from v2.indigo_api import indigo_init

common_api = Blueprint("common_api", __name__)
common_api_logger = logging.getLogger("common")

@common_api.route("/info")
def version():
    """
    Get information about Indigo version
    ---
    tags:
      - version
    responses:
      200:
        description: JSON with indigo version
    """
    versions = {}
    indigo = indigo_init()
    versions["indigo_version"] = indigo.version()

    return jsonify(versions), 200, {"Content-Type": "application/json"}
