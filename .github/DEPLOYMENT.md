# Production deployment configuration

The `Deploy production` workflow runs whenever a commit reaches `main` (including a merged pull request). It connects to the Ubuntu server and checks out the exact commit that triggered the workflow.

Create a GitHub **production environment** named `production`, then add these environment secrets:

| Secret | Value |
| --- | --- |
| `DEPLOY_HOST` | Server IP address or hostname. |
| `DEPLOY_PORT` | SSH port, usually `22`. |
| `DEPLOY_USER` | Unprivileged SSH user that owns the deployed repository. |
| `DEPLOY_PATH` | Absolute path to the Laravel repository on the server, such as `/var/www/AlasManagement`. |
| `DEPLOY_SSH_PRIVATE_KEY` | Private key for the deployment-only SSH key pair. |
| `DEPLOY_KNOWN_HOSTS` | The server's SSH host key, from `ssh-keyscan -H your-server-hostname`. Verify this fingerprint with your server provider before saving it. |

Server requirements: Git, Composer, PHP and the extensions required by the app must already be installed; the deployment user must own `storage/` and `bootstrap/cache/`. The server repository must already have its `origin` remote set to an SSH URL the server can read, such as `git@github-personal:joseylaya/AlasManagement.git`.

The workflow runs `composer install`, database migrations, Laravel cache cleanup, config/event/route/view caching, and `queue:restart`. Back up the database and use a maintenance window for migrations that are not backward compatible.
