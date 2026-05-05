# ADR-003: Stack and infrastructure decisions

**Date:** 2026-05-02  
**Status:** Accepted

---

## Context

To have a solid and ready to scale mvp of Momentum, we need to choose the stack carefully.  
As of right now, the team only has one developer and a deadline of arround 5 months.  
Taking this into account, I would like to go with a stack I had experiences with to limit the learning phase.  
This stack should also permit us to grow and scale.

## Decisions

The stack will be as follow:

- Symfony as backend API
- ReactJS as frontend library, with the additional ReactRouter and ReactQuery packages
- Vitejs as frontend builder
- Typescript
- MariaDB
- Redis for cache
- RabbitMQ for queue management

We will be using the docker image [dunglas/symfony-docker](https://github.com/dunglas/symfony-docker).

Make commands will be written to ease up the project repetitives task like the install, the dev build etc...

We will use Github for the repository, and github actions to handle CI/CD.  
There will be 4 actions:

1. Code quality check (PHPStan, php and js linters, type checking with Typescript)
2. Run tests
3. Build
4. Deploy

The first three will be run in parallel. If they are successful, the last action (deploy) will be possible.

The project will be hosted using Railway.

## Rationale

The choice of the Symfony framework over Laravel, the other major PHP framework, resides in it's component-based architecture, which would allow us to move towards hexagonal structures later on.  
Concerning MariaDB, the choice was made to have a completly open-source database, and the fact that Postgresql seems overkill for our project's needs.  
This stack is robust and well-known which will facilitate the arrival of new dev on the project.  
This will also permit us to scale without too much worry.  
The downside is that it will take some time to put everything in place at first.

## Alternatives considered

Next.Js was considered at first, but it would only have bring more complexity.  
Redis queue was also a strong candidate, but we decided to go for RabbitMQ as it's more reliable for this task.

## Consequences

Every dev should install docker on their laptop.  
We will have more services to maintain with the usage of RabbitMQ.  
We will depend on Railway for deployement.
