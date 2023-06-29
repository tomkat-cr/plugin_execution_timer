# .DEFAULT_GOAL := local
# .PHONY: tests
SHELL := /bin/bash

# General Commands
help:
	cat Makefile

zip:
	bash ./scripts/prepare_plugin_distr.sh
