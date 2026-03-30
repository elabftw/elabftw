#!/usr/bin/env python3
import sys
import json
import base64
from openbabel import pybel
from typing import Dict

async def app(scope, receive, send):
    assert scope['type'] == 'http'

    body = await read_body(receive)
    try:
        data = json.loads(body)
    except json.JSONDecodeError:
        message = {
            'error': 'Invalid JSON'
        }
        await send_response(send, 400, message)
        return

    fmt = data.get('fmt')
    if fmt != 'smi':
        message = {
            "error": "Invalid value for 'fmt'. Expected 'smi'."
        }
        await send_response(send, 400, message)
        return

    content = data.get('data')
    if not content:
        message = {
            "error": "Missing data!"
        }
        await send_response(send, 400, message)
        return

    try:
        mol = pybel.readstring(fmt, content)
    except OSError:
        message = {
            "error": "Incorrect SMILES received!"
        }
        await send_response(send, 400, message)
        return
    fp = mol.calcfp()
    response_data = json.dumps({"data": list(fp.fp)}).encode('UTF-8')

    response_headers = [
        (b'content-type', b'application/javascript'),
        (b'content-length', str(len(response_data)).encode())
    ]
    await send({
        'type': 'http.response.start',
        'status': 200,
        'headers': response_headers,
    })
    await send({
        'type': 'http.response.body',
        'body': response_data,
    })

async def read_body(receive):
    """
    Read and return the entire body from an incoming ASGI message.
    """
    body = b''
    more_body = True

    while more_body:
        message = await receive()
        body += message.get('body', b'')
        more_body = message.get('more_body', False)

    return body

async def send_response(send, status: int, message: Dict[str, str]):
    await send({
        'type': 'http.response.start',
        'status': status,
        'headers': [(b'content-type', b'application/json')],
    })
    await send({
        'type': 'http.response.body',
        'body': json.dumps(message).encode('utf-8'),
    })
    return
