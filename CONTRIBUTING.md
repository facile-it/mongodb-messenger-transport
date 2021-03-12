# Contributing
This projects has a local development environment ready which has the following requisites:
 * Docker
 * Docker Compose
 * `make`

The environment has a PHP container (with the lowest supported versions) plus a MongoDB instance.

You can use `make` from the outside to launch tasks: 
 * use `make start` to create the environment and launch a shell inside the PHP container
 * use `make pre-commit-checks` to launch nearly all check that are run in CI too (with no version matrix)

Look into `Makefile` to discover other available tasks.
