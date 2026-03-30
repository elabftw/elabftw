import logging

from flask import Blueprint, jsonify  # type: ignore

# import re
from v2.db.database import db_session
from v2.imago_api import versions as imago_versions
from v2.indigo_api import indigo_init

common_api = Blueprint("common_api", __name__)
common_api_logger = logging.getLogger("common")


@common_api.route("/info")
def version():
    """
    Get information about Indigo, Bingo, Service and Imago versions
    ---
    tags:
      - version
    responses:
      200:
        description: JSON with service, indigo, bingo and imago vesrions
    """
    versions = {}
    if db_session is not None:
        versions["bingo_version"] = db_session.execute(
            "SELECT Bingo.GetVersion();"
        ).fetchone()[0]

    indigo = indigo_init()
    versions["indigo_version"] = indigo.version()

    # with open('/srv/service_version', 'r') as ver:
    #     imago_versions = []
    #     for line in ver.readlines():
    #         if line.startswith("imago-console-"):
    #             imago_versions.append(re.search('imago-console-(.*)\..*', line).group(1))
    #         else:
    #             versions['service_version'] = line.rstrip()
    versions["imago_versions"] = imago_versions

    return jsonify(versions), 200, {"Content-Type": "application/json"}
