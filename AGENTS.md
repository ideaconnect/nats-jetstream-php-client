# Skills

Domain-specific knowledge is available in `.claude/skills/`:

* `jetstream-architecture/` — stream design, subject hierarchies, consumer patterns, retention/delivery policies, JetStream data modelling.
* `jetstream-deployment/` — nats-server configuration, Docker Compose, Kubernetes Helm charts, clustering, TLS, authentication.
* `jetstream-operations/` — troubleshooting, consumer lag, performance tuning, monitoring, Prometheus metrics, nats CLI.

Read the relevant `SKILL.md` before working on tasks that fall within those domains.

# Testing

* To run NATS for testing you can use dockerized instance from `docker` folder.
* To verify changes always run full tests suite:
a) first `composer static-analysis`
b) later `composer test`
