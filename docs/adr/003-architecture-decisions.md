# ADR-003: Architecture decisions

**Date:** 2026-05-02  
**Status:** Proposed

---

## Context

To have a solid and ready to scale mvp of Momentum, we need to choose the architecture carefully.

## Decisions

The architecture will be as follow:

- Symfony as backend API
- ReactJS as frontend library, with the additional ReactRouter and ReactQuery packages
- Vitejs as frontend builder
- Typescript
- MariaDB
- Redis for cache
- RabbitMQ for queue management

We will be using the docker image dunglas/symfony-docker.

We will use Github for the repository, and github actions to handle CI/CD.
There will be 4 actions:

1. Code quality check (PHPStan, php and js linters, type checking with Typescript) that will be run sequentially
2. Build
3. Run tests
4. Deploy (when merging on main and previous steps were successful)

The project will be hosted using Railway.

## Rationale

This architecture is robust and well-known which will facilitate the arrival of new dev on the project.
This will also permit us to scale without too much worry. 
The downside is that it will take some time to put everything in place at first.

## Alternatives considered

Next.Js was considered at first, but it would only have bring more complexity.
Redis queue was also a strong candidate, but we decided to go for RabbitMQ as it's more reliable for this task.

## Consequences

Every dev should install docker on their laptop.
We must be really cautious on code quality and tests before starting a pull-request as failling jobs would make us lost time.

