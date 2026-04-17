#!/usr/bin/env bash
if [ "$TLS_KEYFILE" ]; then
    /srv/fingerprinter/.venv/bin/uvicorn fingerprinter.main:app --host 0.0.0.0 --port 8000 --ssl-keyfile=$TLS_KEYFILE --ssl-certfile=$TLS_CERTFILE
else
    /srv/fingerprinter/.venv/bin/uvicorn fingerprinter.main:app --host 0.0.0.0 --port 8000
fi
