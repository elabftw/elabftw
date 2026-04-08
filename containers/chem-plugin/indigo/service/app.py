#!/usr/bin/env python
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

import logging
import sys
from argparse import ArgumentParser

from flask import Flask  # type: ignore
from werkzeug.serving import run_simple  # type: ignore

from v2.common_api import common_api
from v2.indigo_api import indigo_api

app = Flask(__name__)
app.config.from_object("v2.common.config")
app.register_blueprint(indigo_api, url_prefix="/v2/indigo")
app.register_blueprint(common_api, url_prefix="/v2")

logging.basicConfig(
    stream=sys.stdout,
    format="[%(asctime)s: %(levelname)-8s/%(filename)s:%(lineno)d]  %(message)s",
    level=app.config.get("LOG_LEVEL"),
)

def run_server(port, host="127.0.0.1", debug=False):
    run_simple(
        host,
        port,
        app,
        use_reloader=debug,
        use_debugger=debug,
        use_evalex=debug,
    )

if __name__ == "__main__":
    parser = ArgumentParser()
    parser.add_argument(
        "-s",
        "--server",
        action="store_true",
        dest="run_server",
        default=False,
        help="Run local server",
    )
    parser.add_argument(
        "-p",
        "--port",
        action="store",
        dest="port",
        type=int,
        default=5000,
        help="Specify port",
    )

    options = parser.parse_args()
    if options.run_server:
        run_server(options.port)
