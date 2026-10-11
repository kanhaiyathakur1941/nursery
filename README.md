# Nursery room occupancy

A small Laravel API for a multi-tenant nursery platform. Staff check a child into a room and check them out when they leave. A manager can ask who is in a room right now.

Nursery groups are tenants. A request for one tenant cannot read or change another tenant's rooms, children, or check-ins. Authentication is assumed to be handled already. This service trusts the `X-Tenant-ID` header as the tenant for the request.

## Run it

Requirements: PHP 8.2, Composer, and the SQLite extension.

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan serve
```

SQLite is the database this project runs on, including the test suite (in memory).

### Endpoints

Send `X-Tenant-ID` with the tenant's numeric id. `Accept: application/json` is optional; these routes always respond with JSON.

| Method | Path | Body | Success |
| --- | --- | --- | --- |
| `POST` | `/rooms/{room}/check-ins` | `{ "child_id": 1 }` | `201` |
| `POST` | `/check-ins/{id}/check-out` | none | `200` |
| `GET` | `/rooms/{room}/occupants` | none | `200` |

Occupants are the children with an open check-in (`checked_out_at` is null), oldest check-in first.

### Errors

| Status | When |
| --- | --- |
| `400` | `X-Tenant-ID` is missing, not a number, or not a known tenant |
| `404` | The room, child, or check-in is not in this tenant, or the check-in is already closed |
| `409` | The child already has an open check-in |
| `422` | `child_id` is missing or not an integer |

A cross-tenant id is `404`, not `403`, so the response does not confirm that the other tenant's record exists.

## Tests

```bash
php artisan test
```

## A child cannot be in two rooms

If staff check a child into a second room while they are still checked into the first, the API rejects the request with `409 Conflict` and leaves the original visit open. The response includes the room id of that open visit so staff can see where the child already is.

Rejecting is safer than moving the child automatically. An automatic move would hide a missed checkout, which is the mistake a manager is trying to notice. Moving and also returning a warning needs a client to show that warning, and this track has no user interface. The rule is also enforced in the database: a partial unique index allows only one open check-in per child, including when two requests arrive together.

Checking into the same room twice is the same conflict. After a checkout, the child can be checked in again.

## Assumptions

- The caller is already authenticated. This API does not check that the signed-in user belongs to `X-Tenant-ID`.
- A tenant is a nursery group. Rooms and children belong directly to that tenant. There is no separate nursery site under the group.
- Ids are integers.
- Only SQLite is supported. The partial unique index uses SQLite syntax.

## Left out on purpose

- The React room view (Track B).
- Users, passwords, and sessions.
- Creating rooms and children over HTTP. Tests and factories insert them. The brief only asks for check-in, check-out, and the occupant list.
- Pagination, audit history beyond the check-in rows, and an idempotency key.

## With more time

I would record who performed each check-in, add an idempotency key so a retried request cannot double-write, and paginate the occupant list once a room can hold more children than one response should return. I would also stop trusting `X-Tenant-ID` on its own and bind it to the authenticated user.

## Running on AWS with ECS and Fargate

I would run the Laravel application as a container on ECS with Fargate, behind an Application Load Balancer. The image contains PHP, the HTTP server, the application code, and the Composer dependencies. It does not contain secrets or the database. Non-secret settings are environment variables on the task definition. The application key, the database password, and any other credentials come from AWS Secrets Manager and are injected when the task starts. Laravel reads them from the environment. The task role is the only AWS identity the container needs, so the image has no long-lived keys.

The database is RDS in private subnets. The Fargate tasks run in those same private subnets. A security group allows the database port only from the ECS service. Migrations run as a one-off task, not on every web request. Fargate has no durable disk, so SQLite stays in the test suite and production state lives in RDS.

Logs go to CloudWatch in JSON, with the tenant id and the request id on each line. I would page on a rising rate of HTTP 500 responses, unhealthy load-balancer targets, failed deployments, and RDS CPU, storage, or connection failures. A `409` is an expected staff mistake: count it on a dashboard, and do not page on it. I would also alarm when the service cannot open a database connection, because that is the failure that stops a manager from seeing who is in a room.

## How AI tools were used

Cursor, with Grok, drafted the Laravel structure from this brief: models, the tenant middleware, the three actions, and the Pest tests. I kept the `409` decision, the choice to answer cross-tenant lookups with `404`, and the SQLite partial unique index, and I checked the tests against those rules rather than accepting a happy-path-only suite. I did not add a React client or extra packages the brief said to skip.
