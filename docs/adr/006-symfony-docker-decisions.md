# ADR-006: Symfony docker decission

**Date:** 2026-05-21  
**Status:** Proposed

---

## Context

As mention in [003-stack-and-infrastructure-decisions.md](./003-stack-and-infrastructure-decisions.md), this project use the official docker image of symfony: [dunglas/symfony-docker](https://github.com/dunglas/symfony-docker).  
Some choices have been made to fully customize the docker image into what fits better for Momentum.

## Decision

Instead of only cloning the docker image, and build everything inside the folder, we have decided to make a separation between "services".  
The main structure is as follow:

1. api: this contains the symfony api, all the backend is written inside this folder
2. docker: this is the clone of the symfony-docker image. All modification to the stack, edit of containers or addition, is to be made here.
3. frontend: this is the folder where the front is build, using ReactJS.

## Rationale

This decision was made to have more flexibility about the stack used, and a better separation of concern.
