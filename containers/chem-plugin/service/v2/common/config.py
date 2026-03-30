# Bingo config
import os

BINGO_POSTGRES = {
    "host": "db",
    "port": "5432",
    "database": "postgres",
    "user": "postgres",
    "password": os.environ.get("POSTGRES_PASSWORD", ""),
}

# Flask config

MAX_CONTENT_LENGTH = 1024 * 1024 * 1024
UPLOAD_FOLDER = "/tmp/indigo-service/upload"
ALLOWED_EXTENSIONS = ("sdf", "sd", "gz")

# Celery config
CELERY_broker_url = "redis://localhost:6379/0"
result_backend = "redis://localhost:6379/0"
imports = ("v2.imago_api", "v2.libraries_api")
accept_content = ("json",)
task_serializer = "json"
result_serializer = "json"
CELERY_enable_utc = True
timezone = "Etc/UTC"
worker_pool = "prefork"

# Logging option
LOG_LEVEL = "INFO"
# LOG_LEVEL = 'DEBUG'

# Swagger config

SWAGGER = {
    "swagger_version": "2.0",
    # headers are optional, the following are default
    "headers": [],
    "specs": [
        {
            "version": "0.2.0",
            "title": "Indigo Service API",
            "endpoint": "spec",
            "route": "/spec/",
        }
    ],
}

# imago config

ALLOWED_TYPES = (
    "image/png",
    "image/jpeg",
    "image/gif",
    "image/tiff",
    "image/bmp",
    "image/cmu-raster",
    "image/x-portable-bitmap",
)

IMAGO_VERSIONS = ["2.0.0"]
