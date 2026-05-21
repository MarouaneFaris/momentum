# ADR-006: Symfony docker decisions

**Date:** 2026-05-21  
**Status:** Accepted

---

## Context

As mention in [003-stack-and-infrastructure-decisions.md](./003-stack-and-infrastructure-decisions.md), this project use the official docker image of symfony: [dunglas/symfony-docker](https://github.com/dunglas/symfony-docker).  
By default, this image assume that its root is the Symfony app.
Some choices have been made to fully customize the docker image into what fits better for Momentum.

## Decision

Instead of only cloning the docker image, and build everything inside the folder, we have decided to make a separation between "services".  
The main structure is as follow:

1. api: this contains the symfony api, all the backend is written inside this folder
2. docker: this is the clone of the symfony-docker image. All modification to the stack, edit of containers or addition, is to be made here.
3. frontend: this is the folder where the front is build, using ReactJS.

To be able to add more containers, that could easily access the api and frontend folders, the docker context has been changed to the root of the project.  
Unnecessary files are not included inside the context thanks to the [docker/.dockerignore](../../docker/.dockerignore) file.

## Rationale

Momentum is a monorepo with two distinct services, symfony-docker's default single-app assumption doesn't fit that shape.  
This also ensure a better separation of concern between our services: api and frontend.
