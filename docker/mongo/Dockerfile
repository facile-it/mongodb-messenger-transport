FROM mongo:4.4

USER 999

COPY --chmod=755 --chown=999 docker-healthcheck.sh facile-entrypoint.sh /usr/local/bin/
COPY --chmod=400 --chown=999 mongo.keyfile /

HEALTHCHECK  --start-period=15s --interval=10s CMD ["docker-healthcheck.sh"]

ENTRYPOINT ["facile-entrypoint.sh"]

CMD ["mongod"]
