# indigo-service + elab chem
FROM ubuntu:22.04

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && \
    apt-get upgrade -y
RUN apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        libfreetype6-dev \
        libfontconfig1-dev \
        openbabel \
        python3 \
        python3-openbabel \
        python3-pip \
        python3-wheel \
        python3-setuptools \
        redis-server \
        supervisor \
        unzip \
        uwsgi \
        uwsgi-plugin-python3

# supervisord config
COPY containers/chem-plugin/conf/supervisord.conf /etc/supervisor/supervisord.conf

# Setup celery
COPY containers/chem-plugin/conf/celeryd.conf /etc/default/celeryd
RUN useradd -ms /bin/bash celery
RUN chmod 640 /etc/default/celeryd
COPY containers/chem-plugin/conf/celery.auto.conf /etc/supervisor/conf.d/

# Setup redis
COPY containers/chem-plugin/conf/redis.auto.conf /etc/supervisor/conf.d/

# Setup gunicorn
COPY containers/chem-plugin/conf/gunicorn.auto.conf /etc/supervisor/conf.d/

# Install Imago
RUN set -eux; \
    mkdir -p /srv/imago/imago-console-2.0.0 && \
    cd /srv/imago/imago-console-2.0.0 && \
    curl https://lifescience.opensource.epam.com/downloads/imago-2.0.0/imago-2.0.0-linux64-console.zip -o imago-2.0.0-linux64-console.zip && \
    echo "5c8cc3c1dbf18cb5809386594882da940a0f8cda0afcd4116404bdc115daa9aa imago-2.0.0-linux64-console.zip" | sha256sum -c -; \
    unzip imago-2.0.0-linux64-console.zip && \
    rm imago-2.0.0-linux64-console.zip && \
    chmod -R a+rx imago_console

COPY containers/chem-plugin/service/v2/ /srv/api/v2/
COPY containers/chem-plugin/service/*.py /srv/api/
COPY containers/chem-plugin/service/v2/common/*.py /srv/api/

# Install python dependencies using pip
COPY containers/chem-plugin/service/requirements.txt /opt/
RUN pip3 install --no-cache-dir -r /opt/requirements.txt

# Clean
RUN apt-get --purge remove -y \
    curl \
    libbrotli-dev \
    libc-dev-bin \
    libc6-dev \
    libcrypt-dev \
    libexpat1-dev \
    libfontconfig-dev \
    libfontconfig1-dev \
    libfreetype-dev \
    libfreetype6-dev \
    libnsl-dev \
    libpng-dev \
    libtirpc-dev \
    linux-libc-dev \
    python3-pip \
    python3-wheel \
    unzip \
    uuid-dev \
    zlib1g-dev \
    && apt-get autoremove -y && \
      rm -rf /opt/* /var/lib/apt/lists/*

RUN chown nobody:nogroup /srv/api
RUN mkdir -p /var/log/supervisor /tmp/supervisor \
 && chown -R nobody:nogroup /var/log/supervisor /tmp/supervisor

WORKDIR /app
RUN mkdir fingerprinter
COPY containers/chem-plugin/src/main.py fingerprinter/main.py
COPY containers/chem-plugin/fingerprinter.auto.conf /etc/supervisor/conf.d/
COPY containers/chem-plugin/fingerprinter-entrypoint.sh /usr/local/bin

USER nobody

EXPOSE 8080
WORKDIR /srv/api
CMD ["supervisord", "-c", "/etc/supervisor/supervisord.conf"]
