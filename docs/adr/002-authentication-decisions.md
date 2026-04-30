# ADR-002: Authentication decisions

**Date:** 2026-05-01  
**Status:** Accepted

---

## Context

To launch the MVP of Momentum, questions were raised about the authentication strategy. As the project will have workspaces containing sensitive team data, we need to make sure that the security is taken seriously.
We need to be able to handle basic authentication through the app with credentials (username/password).
We also considered for later versions, the possibility to have social login (i.e. google), and third-party application integration.

## Decision

Use a stateful token-based authentication that would be used for direct login through the web application and for third-party applications (v2). Like this, we will have unified routes for all cases.
The token will be stored in the database and will be revocable if needed.
On basic authentication, a token will be acquired if the user enters the correct combination of username/password.
The token will then be stored using cookies with HttpOnly, SameSite and Secure flags to respect security recommendations.
On third-party application, a token would be created by a user on the application to connect external tools/services.

## Rationale

By adopting stateful auth tokens, we will have a more robust and secure system.
We will be able to revoke access if needed which can't be done if we were to implement stateless tokens.
The main downside will be the speed, as each token check will query the database.

## Alternatives considered

Stateless token (JWT): in our case, as Momentum will have workspaces which need to be secure, using a stateless solution would be problematic if a token gives access to a wrong workspace and we can't revoke the access.
Local storage: this is a common practice to store tokens, but it is prone to XSS attacks, which makes the cookie storage a better option.

## Consequences

We will need to use the cookie storage on the application's frontend with the correct security flags.
We should expect some latency as this system uses the database to check the validity of the token. We will probably implement some caching strategies with redis for example in a future version to improve performance.
We have to keep in mind the multiple auth evolutions: third-party, 2FA, social login.
We should also handle and test the revoke system.
