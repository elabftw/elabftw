#!/usr/bin/env python

import logging
import sys
from argparse import ArgumentParser

from flask import Flask  # type: ignore
from werkzeug.serving import run_simple  # type: ignore

from v2.common_api import common_api
from v2.indigo_api import indigo_api

app = Flask(__name__)
app.config.from_pyfile("config.py")
app.register_blueprint(indigo_api, url_prefix="/v2/indigo")
app.register_blueprint(common_api, url_prefix="/v2")

logging.basicConfig(
    stream=sys.stdout,
    format="[%(asctime)s: %(levelname)-8s/%(filename)s:%(lineno)d]  %(message)s",
    level=app.config.get("LOG_LEVEL"),
)


def run_server(port):
    run_simple(
        "0.0.0.0",
        port,
        app,
        use_reloader=True,
        use_debugger=True,
        use_evalex=True,
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
