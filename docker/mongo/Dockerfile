FROM mongo:5.0@sha256:54bcd8da3ea5eec561b68c605046c55c6b304387dc4c2bf5b3a5f5064fbb7495

USER 999

COPY --chmod=755 --chown=999 docker-healthcheck.sh facile-entrypoint.sh /usr/local/bin/
COPY --chmod=400 --chown=999 mongo.keyfile /

HEALTHCHECK  --start-period=15s --interval=10s CMD ["docker-healthcheck.sh"]

ENTRYPOINT ["facile-entrypoint.sh"]

CMD ["mongod"]
