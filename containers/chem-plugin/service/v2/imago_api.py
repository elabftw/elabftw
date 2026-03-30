import json
import logging
import os
import re
import subprocess
import traceback
from time import time
from typing import List

from flask import Blueprint, request  # type: ignore
from indigo import Indigo  # type: ignore
from indigo.inchi import IndigoInchi  # type: ignore
from indigo.renderer import IndigoRenderer  # type: ignore

from .celery_app import celery
from .common import config

imago_api = Blueprint("imago", __name__)
imago_api_logger = logging.getLogger("imago")
imago_api.config = config.__dict__  # type: ignore
imago_api.indigo = Indigo()  # type: ignore
imago_api.renderer = IndigoRenderer(imago_api.indigo)  # type: ignore
imago_api.indigo_inchi = IndigoInchi(imago_api.indigo)  # type: ignore
allowed_types = imago_api.config["ALLOWED_TYPES"]  # type: ignore
versions: List[str] = imago_api.config["IMAGO_VERSIONS"]  # type: ignore
# with open('/srv/service_version', 'r') as ver:
#     for line in ver.readlines():
#         if line.startswith("imago-console-"):
#             versions.append(re.search('imago-console-(.*)\..*', line).group(1))


@celery.task(bind=True)
def recognize_image(self, args):
    """
    Celery task to pass image as an asynchronous process for its recognizing.
    Image passed as a mol file in string format to GET request, which use id to retrieve it.
    :param self:
    :param args: list of imago console commands
    :return: dictionary with recognized molecule as a mol file as a string.
    """
    try:
        self.update_state(state="PROCESSING", meta={"stage": "RECOGNIZING"})
        result_dict = imago_recognize(args)
        self.update_state(state="SUCCESS", meta=result_dict)
        return result_dict
    except Exception as e:
        self.update_state("FAILURE", meta={"error": str(e)})
        return {"error": "Internal server error: {}".format(str(e))}, 500


@celery.task(bind=True)
def remove_task(self, task_id, args):
    """
    Deletes task by id after. Because forget() behavior  in celery is, to a certain extent, unpredictable ,
    image deleted after launching task to ensure removing task.
    :param self:
    :param task_id: id of task result to be deleted
    :param args: list of imago console commands
    :return:
    """
    pass_args.AsyncResult(task_id).forget()
    for arg in args[1:]:
        # deletes image files by path in arg
        if os.path.isfile(arg):
            os.remove(arg)


@celery.task(bind=True)
def pass_to_res(self, args, time=None):
    """
    Parent task to remove_task and pass_args. Used to return id of pass_args
    and launch time limit in seconds for task's results existence if it was passed in request.
    :param self:
    :param args: list of imago console commands
    :param time: time limit in seconds of task existence
    :return: task id for retrieving results by POST request
    """
    task_id = pass_args.apply_async(args=(args,)).id
    if time:
        # launch deleting task results after certain
        remove_task.apply_async(
            kwargs={"task_id": task_id, "args": args}, countdown=time
        )
    return task_id


@celery.task(bind=True)
def pass_args(self, args):
    """
    Passes arguments to be used for imago for saved image.
    Arguments will be retrieved after launching request by task id.
    :param self:
    :param args: list of imago console commands
    :return: dictionary with arguments as a list
    """
    try:
        result = {"args": args}
        self.update_state(state="SUCCESS", meta=result)
        return result
    except Exception as e:
        self.update_state("FAILURE", meta={"error": str(e)})
        return {"error": "Internal server error: {}".format(str(e))}, 500


def save_file(stream, f_type):
    """
    Saves byte stream of image into selected type.
    Image saved in selected format, which is passed by POST request.
    :param stream: image as a stream of bytes
    :param f_type: image format
    :return: path to image on server
    """
    path = os.path.join(
        imago_api.config["UPLOAD_FOLDER"],
        "{0}.{1}".format(int(time() * 1000), f_type),
    )
    with open(path, "wb") as f:
        data = stream.read()
        f.write(data)
    return path


def save_config(settings):
    """
    Gets settings in JSON format to be passed in text file for imago console
    and save it on server. Return configuration file in txt format
    :param settings: settings in JSON to be processed in configuration file
    :return: path to configuration file on server
    """
    path = os.path.join(
        imago_api.config["UPLOAD_FOLDER"],
        "{0}.{1}".format(int(time() * 1000), "txt"),
    )
    data = json.loads(settings)
    with open(path, "w") as f:
        for param, value in data.items():
            f.write(param + " = " + value + ";")
    return path


def imago_recognize(args):
    """
    Recognize image by Imago and returns mol file of molecule in string format
    :param args: list of imago console commands
    :return: dictionary with mol file in string format
    """
    proc = subprocess.Popen(
        args, stdout=subprocess.PIPE, stderr=subprocess.PIPE, shell=False
    )
    _out, _err = proc.communicate()
    mol_str = ""
    if os.path.isfile(args[-1]):
        with open(args[-1], "r") as f:
            mol_str = f.read()
    # imago_api.indigo.setOption("render-coloring", True)
    # imago_api.indigo.setOption("render-output-format", 'png')
    # imago_api.indigo.setOption("render-image-width", 400)
    # imago_api.indigo.setOption("render-image-height", 300)
    # m = imago_api.indigo.loadMolecule(mol_str)
    # result = imago_api.renderer.renderToBuffer(m)
    # result = result.tostring() if sys.version_info < (3, 2) else result.tobytes()
    return {"mol_str": mol_str}


@imago_api.route("/uploads", methods=["POST"])
def imago_upload_post():
    """
    Upload and recognize image
    ---
    tags:
        - imago
    description: 'POST image of molecule for image'
    consumes:
        - image/png,
        - image/jpeg,
        - image/gif,
        - image/tiff,
        - image/bmp,
        - image/cmu-raster,
        - image/x-portable-bitmap,
    parameters:
        - name: image_request
          in: body
          description: 'Image to process in Imago'
          required: true
          type: string
          format: binary
        - name: version
          in: json_request
          description: 'Version of Imago'
          type: string
        - name: settings
          in: json_request
          description: 'Settings for Imago'
          type: json
        - name: action
          in: json_request
          description: "Determines logic of POST request: send image or wait until settings passed"
          type: string
        - name: expire
          in: json_request
          description: "Time for image POST requeust to expire during waiting for settings"
          type: float
    responses:
        200:
            description: 'Task id for obtaining molecule'
            schema:
                type: string
        204:
            description: 'Imago not recognized image'
            schema:
                id: Error
                required:
                    - error
                properties:
                    error:
                        type: string
        400:
            description: A problem with supplied client data
            schema:
                id: Error
                required:
                    - error
                properties:
                    error:
                        type: string
        415:
            description: Supplied image format are not supported
            schema:
                id: Error
                required:
                    - error
                properties:
                    error:
                        type: string
        5XX:
            description: 'Internal service error'
            schema:
                id: Error
                required:
                    - error
                properties:
                    error:
                        type: string
    """
    args = []
    full_mime_type = request.headers.get("Content-Type")
    params = request.args
    # gets version from params or pass the latest
    if "version" in params:
        if params["version"] in versions:
            version = "imago-console-" + params["version"]
        else:
            return (
                {
                    "error": "Incorrect version {0}, should be one of [{1}]".format(
                        request.headers["Version"], ", ".join(versions)
                    )
                },
                400,
            )
    else:
        version = "imago-console-" + versions[-1]
    args.append(os.path.join("/srv", "imago", version, "imago_console"))

    # gets setting from params
    if "settings" in params:
        try:
            settings = save_config(params["settings"])
            args.extend(["-config", settings])
        except Exception as e:
            return {"error": "Returned with error {0}".format(e)}, 400

    # gets tine limit for task id existence of POST wait request
    if "expires" in params:
        expire = float(params["expires"])
    else:
        expire = None

    # optional parameters might be appended, get just the type
    mime_type = re.search(r"\A([^;]+)", full_mime_type)
    if not mime_type:
        return (
            {
                "error": "Incorrect Content-Type '{0}', should be one of [{1}]".format(
                    full_mime_type, ", ".join(allowed_types)
                )
            },
            415,
        )

    mime_type = mime_type.group(1)
    imago_api_logger.info(
        "[REQUEST] POST /imago/uploads Content-Type: {0}".format(mime_type)
    )

    if mime_type not in allowed_types:
        return (
            {
                "error": "Incorrect Content-Type '{0}', should be one of [{1}]".format(
                    mime_type, ", ".join(allowed_types)
                )
            },
            415,
        )
    try:
        select_exten = {
            "cmu-raster": "ras",
            "tiff": "tiff",
            "png": "png",
            "jpeg": "jpg",
            "x-portable-bitmap": "pbm",
            "bmp": "bmp",
            "svg+xml": "svg",
            "gif": "gif",
        }

        type_of_image = re.search("/([a-z-+]+)", mime_type).group(1)

        f_type = select_exten[type_of_image]
        path = save_file(request.stream, f_type)
        mol_path = "{}.mol".format(path)
        args.extend([path, "-o", mol_path])
        # Determines logic of POST request
        # if action wait selected image will be saved on server
        # and will wait for settings to be sent
        # else pass image for GET request
        if "action" in params:
            if params["action"] == "wait":
                pass_task = pass_to_res.apply_async(args=(args, expire))
                id_args = pass_to_res.AsyncResult(pass_task.id).get()
                return {"upload_id": id_args}
            else:
                return {"error": "Incorrect action parameter."}, 500

        else:
            upload = recognize_image.apply_async((args,))
        if "mol_str" in upload.get():
            if not upload.get()["mol_str"]:
                return {"error": "Imago returned empty string."}, 204
        return {"upload_id": upload.id}
    except Exception as e:
        imago_api_logger.error(
            "[RESPONSE-500] internal error: {}\n{}".format(
                str(e), traceback.format_exc()
            )
        )
        return {"error": "Internal server error."}, 500


@imago_api.route("/uploads/<upload_id>", methods=["GET"])
def upload_status_get(upload_id):
    """
    Check upload status for selected upload_id
    ---
    tags:
        - imago
    description: 'Check upload status for image and returns processed image'
    responses:
        200:
            description: 'Result JSON with mol file as string'
            schema:
                id: UploadResponse
                properties:
                    state:
                        type: string
                        example: SUCCESS
                    metadata:
                        type: object
                        properties:
                            mol_str:
                                type: string
                                description: "Mol file in string format"
        400:
            schema:
                $ref: "#/definitions/Error"

        5XX:
            description: Internal service error
            schema:
                $ref: "#/definitions/Error"

    """
    imago_api_logger.info("[REQUEST] GET /imago/uploads/{0}".format(upload_id))
    try:
        # retrieve recognized molecule in mol_format
        task = recognize_image.AsyncResult(upload_id)
        result_dict = {"state": task.state}
        if task.info:
            result_dict["metadata"] = task.info
            if "error" in json.dumps(task.info):
                imago_api_logger.error(
                    "[RESPONSE-400] {0}".format(result_dict)
                )
                return result_dict, 400
        imago_api_logger.info(
            "[RESPONSE-200] {0}".format(result_dict["state"])
        )
        return result_dict
    except Exception as e:
        imago_api_logger.error(
            "[RESPONSE-500] internal error: {}\n{}".format(
                str(e), traceback.format_exc()
            )
        )
        return {"error": "Internal server error."}, 500


@imago_api.route("/uploads/<upload_id>", methods=["POST"])
def upload_status_post(upload_id):
    """
    Pass configuration for Imago to specific image
    ---
    tags:
        - imago
    description: 'Pass settings to Imago and process uploaded image'
    parameters:
        - name: action
          in: json_request
          required: true
          type: string
          description: 'Parameter to run Imago with passed properties'
        - name: settings
          in: json_request
          type: object
          description: 'Parameters for Imago in JSON'
    responses:
        200:
            description: 'Image processed'
            schema:
                    $ref: "#/definitions/UploadResponse"
        204:
            description: 'Imago not recognized image'
            schema:
                $ref: "#/definitions/Error"
        400:
            description: A problem with supplied client data
            schema:
                $ref: "#/definitions/Error"
        410:
            description: Image are not available because of time limit
            schema:
                $ref: "#/definitions/Error"

        5XX:
            description: Internal service error
            schema:
                $ref: "#/definitions/Error"

    """
    params = request.args
    imago_api_logger.info(
        "[REQUEST] POST /imago/uploads/{0}".format(upload_id)
    )
    # assert if correct action were sent in request
    if "action" in params:
        if params["action"] != "run":
            imago_api_logger.error(
                "[RESPONSE-406] Incorrect parameter {0} in request.".format(
                    request.headers["action"]
                )
            )
            return (
                {
                    "error": "Incorrect parameter {0} in request.".format(
                        request.headers["action"]
                    )
                },
                400,
            )
    else:
        imago_api_logger.error(
            "[RESPONSE-400] Parameter action not in request."
        )
        return {"error": "Paramter action not in request."}, 400

    try:
        # Retrieve arguments for imago console command by task id.
        if pass_args.AsyncResult(upload_id).info:
            param = pass_args.AsyncResult(upload_id).get()
            if not os.path.isfile(param["args"][-3]):
                imago_api_logger.error(
                    "[RESPONSE-410] Image are not available because of time limit."
                )
                return (
                    {"error": "Image are not available because of time limit"},
                    410,
                )
        else:
            imago_api_logger.error(
                "[RESPONSE-410] Image are not available because of time limit."
            )
            return (
                {"error": "Image are not available because of time limit"},
                410,
            )

        if "error" in param:
            imago_api_logger.error("[RESPONSE-400] {0}".format(param))
            return param, 400
        args = param["args"]

        # Save settings in config file and extend args with -config oprtion
        if request.data:
            try:
                settings = save_config(request.get_json())
                for x in reversed(["-config", settings]):
                    args.insert(1, x)
            except Exception as e:
                return {"error": "Returned with error {0}".format(e)}, 400

        result = imago_recognize(args)
        if not result["mol_str"]:
            imago_api_logger.error(
                "[RESPONSE-204] Imago returned empty string."
            )
            return result, 204
        imago_api_logger.info("[RESPONSE-200] SUCCESS")
        return result
    except Exception as e:
        imago_api_logger.error(
            "[RESPONSE-500] internal error: {}\n{}".format(
                str(e), traceback.format_exc()
            )
        )
        return {"error": "Internal server error."}, 500
