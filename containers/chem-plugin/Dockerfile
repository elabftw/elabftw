# indigo-service + elab chem
# https://hub.docker.com/hardened-images/catalog/dhi/python/images/python%2Fdebian-13%2F3.13/sha256-31c804314f84ac566f2ada1a64cfa17ba365e9da16eac4da7e219b20552fafae
# Python 3.13.x hardened image
FROM dhi.io/python@sha256:61594a3427b191f6978131c2d94b2d90113e413126f5a17ab9e048ccd3863248

ENV DEBIAN_FRONTEND=noninteractive
ENV HOME=/home/nobody
ENV UV_CACHE_DIR=/home/nobody/.cache
# https://docs.astral.sh/uv/guides/integration/docker/#compiling-bytecode
ENV UV_COMPILE_BYTECODE=1

# distroless image 0.11.2
# got index digest with "docker buildx imagetools inspect docker.io/astral/uv:0.11.2"
COPY --from=ghcr.io/astral-sh/uv@sha256:c4f5de312ee66d46810635ffc5df34a1973ba753e7241ce3a08ef979ddd7bea5 /uv /uvx /bin/

RUN apt-get update && \
    apt-get install -y --no-install-recommends \
        openbabel \
        supervisor && \
    apt-get autoremove -y && \
      rm -rf /var/lib/apt/lists/*

# supervisord config
COPY containers/chem-plugin/conf/supervisord.conf /etc/supervisor/supervisord.conf
RUN mkdir -p /var/log/supervisor /tmp/supervisor \
 && chown -R nobody:nobody /var/log/supervisor /tmp/supervisor

# Indigo API
WORKDIR /srv/indigo
COPY containers/chem-plugin/indigo/.python-version .
COPY containers/chem-plugin/indigo/pyproject.toml .
COPY containers/chem-plugin/indigo/uv.lock .
COPY containers/chem-plugin/indigo/service/v2/ /srv/indigo/v2/
COPY containers/chem-plugin/indigo/service/*.py /srv/indigo/
COPY containers/chem-plugin/indigo/service/v2/common/*.py /srv/indigo/
RUN uv sync --frozen --no-dev --no-editable
# supervisor service for indigo api using gunicorn
COPY containers/chem-plugin/conf/gunicorn.auto.conf /etc/supervisor/conf.d/

# FINGERPRINTER
WORKDIR /srv/fingerprinter
COPY containers/chem-plugin/fingerprinter .
COPY --chmod=755 containers/chem-plugin/fingerprinter-entrypoint.sh /usr/local/bin/
RUN uv sync --frozen --no-dev --no-editable
# supervisor service for fingerprinter api using uvicorn
COPY containers/chem-plugin/conf/fingerprinter.auto.conf /etc/supervisor/conf.d/

# chown is necessary to fix permission issue on cache folder when executing as nobody
RUN chown -R nobody:nobody /home/nobody/.cache

USER nobody

EXPOSE 8000 8080
WORKDIR /srv
CMD ["supervisord", "-c", "/etc/supervisor/supervisord.conf"]
