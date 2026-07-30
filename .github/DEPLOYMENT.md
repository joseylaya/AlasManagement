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

## Set up the server's GitHub SSH key (Ubuntu)

This is the **server-to-GitHub** key used by `git fetch` and `git pull`. It is different from `DEPLOY_SSH_PRIVATE_KEY`, which is the **GitHub Actions-to-server** key.

1. Log in to the server as the same non-root user that owns the Laravel application, then generate a dedicated key without a passphrase. Do not use your personal GitHub key.

   ```bash
   ssh-keygen -t ed25519 -C "alas-management-production" -f ~/.ssh/alas_management_deploy -N ""
   chmod 700 ~/.ssh
   chmod 600 ~/.ssh/alas_management_deploy
   chmod 644 ~/.ssh/alas_management_deploy.pub
   ```

2. Print the public key and add it in GitHub at **AlasManagement → Settings → Deploy keys → Add deploy key**. Give it a name such as `Production Ubuntu server`; leave **Allow write access** disabled because deployments only need to read the repository.

   ```bash
   cat ~/.ssh/alas_management_deploy.pub
   ```

3. Configure SSH to use the new key for GitHub and verify GitHub's host key. Add the following block to `~/.ssh/config` with an editor such as `nano`; this prevents Git from using an unrelated key on the server.

   ```bash
   ssh-keyscan -H github.com >> ~/.ssh/known_hosts
   chmod 600 ~/.ssh/known_hosts
   nano ~/.ssh/config
   chmod 600 ~/.ssh/config
   ```

   Add this configuration in the editor:

   ```sshconfig
   Host github-alas-management
       HostName github.com
       User git
       IdentityFile ~/.ssh/alas_management_deploy
       IdentitiesOnly yes
   ```

4. Verify access, then clone the repository or set the remote to SSH. GitHub will respond with a successful-authentication message but will not provide shell access.

   ```bash
   ssh -T git@github-alas-management
   git clone git@github-alas-management:joseylaya/AlasManagement.git /var/www/AlasManagement
   ```

   If the repository is already present, update its remote instead:

   ```bash
   cd /var/www/AlasManagement
   git remote set-url origin git@github-alas-management:joseylaya/AlasManagement.git
   git fetch origin main
   ```
