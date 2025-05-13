# WordPress Deployment on Dokku with CI/CD and Monitoring

This project demonstrates deploying a WordPress application on a single server using Dokku, implementing a CI/CD pipeline with GitHub Actions, setting up monitoring with Prometheus and Grafana, and establishing a backup strategy.

## Setup Guide

This guide assumes you have an Ubuntu EC2 instance with a domain pointing to its IP address (`s14.michaeloxo.tech` in this case) and a GitHub repository containing the standard WordPress code.

1.  **Provision EC2 Instance & Install Dokku:**

    * Launch an Ubuntu EC2 instance.

    * Connect via SSH.

    * Install Dokku by following the official documentation (<https://dokku.com/docs/getting-started/install/>).

    * Complete the initial Dokku setup via the web installer or CLI, setting the global domain to `s14.michaeloxo.tech` and adding your SSH public key.

2.  **Configure Dokku Application:**

    * On your EC2 instance, create the Dokku app:

        ```bash
        dokku apps:create devops-wordpress
        ```

    * Install the MariaDB plugin:

        ```bash
        sudo dokku plugin:install [https://github.com/dokku/dokku-mariadb.git](https://github.com/dokku/dokku-mariadb.git) mariadb
        ```

    * Create the database service:

        ```bash
        dokku mariadb:create devops-wordpress-db
        ```

    * Link the database to the app:

        ```bash
        dokku mariadb:link devops-wordpress-db devops-wordpress
        ```

    * Set necessary WordPress environment variables (salts, keys, URLs):

        ```bash
        dokku config:set devops-wordpress AUTH_KEY='...' ... # (All 8 salts/keys)
        dokku config:set devops-wordpress WP_HOME="[https://devops.s14.michaeloxo.tech](https://devops.s14.michaeloxo.tech)"
        dokku config:set devops-wordpress WP_SITEURL="[https://devops.s14.michaeloxo.tech](https://devops.s14.michaeloxo.tech)"
        ```

    * Set up persistent storage mounts for uploads, plugins, and themes:

        ```bash
        sudo mkdir -p /var/lib/dokku/data/storage/devops-wordpress/{uploads,plugins,themes}
        sudo chown -R 33:33 /var/lib/dokku/data/storage/devops-wordpress/ # Adjust user ID if needed
        dokku storage:mount devops-wordpress /var/lib/dokku/data/storage/devops-wordpress/uploads:/var/www/html/wp-content/uploads
        dokku storage:mount devops-wordpress /var/lib/dokku/data/storage/devops-wordpress/plugins:/var/www/html/wp-content/plugins
        dokku storage:mount devops-wordpress /var/lib/dokku/data/storage/devops-wordpress/themes:/var/www/html/wp-content/themes
        ```

    * Add the custom domain to the app:

        ```bash
        dokku domains:add devops-wordpress devops.s14.michaeloxo.tech
        ```

3.  **Initial Deployment:**

    * On your local machine, clone your WordPress GitHub repository.

    * Add the Dokku server as a remote:

        ```bash
        git remote add dokku dokku@s14.michaeloxo.tech:devops-wordpress
        ```

    * Ensure your local machine's SSH public key is added to Dokku (`dokku ssh-keys:add`).

    * Push your code to trigger the initial deployment:

        ```bash
        git push dokku main
        ```

    * Complete the WordPress web installation at `http://devops.s14.michaeloxo.tech`.

4.  **Enable Let's Encrypt SSL:**

    * On your EC2 instance, install the plugin:

        ```bash
        sudo dokku plugin:install [https://github.com/dokku/dokku-letsencrypt.git](https://github.com/dokku/dokku-letsencrypt.git) letsencrypt
        dokku config:set --global DOKKU_LETSENCRYPT_EMAIL=your-email@example.com
        ```

    * Enable SSL for the app (ensure DNS for `devops.s14.michaeloxo.tech` points to your EC2 IP):

        ```bash
        dokku letsencrypt:enable devops-wordpress
        dokku letsencrypt:cron-job --add
        ```

    * Update WordPress URLs to HTTPS in Dokku config:

        ```bash
        dokku config:set devops-wordpress WP_HOME="[https://devops.s14.michaeloxo.tech](https://devops.s14.michaeloxo.tech)" WP_SITEURL="[https://devops.s14.michaeloxo.tech](https://devops.s14.michaeloxo.tech)"
        dokku ps:restart devops-wordpress
        ```

    * Fix mixed content by running a search/replace in the WordPress database (e.g., using the Better Search Replace plugin) to change `http://devops.s14.michaeloxo.tech` to `https://devops.s14.michaeloxo.tech`.

5.  **Set up CI/CD with GitHub Actions:**

    * Generate a separate SSH key pair for GitHub Actions.

    * Add the *public* key to Dokku (`dokku ssh-keys:add`).

    * Add the *private* key as a GitHub Secret named `SLACK_WEBHOOK` (or `SLACK_WEBHOOK_URL` depending on the action used).

    * Create a workflow file (`.github/workflows/deploy.yml`) in your GitHub repository.

    * Configure the workflow to checkout code (`fetch-depth: 0`), set up SSH agent using the secret, add the Dokku remote, and force push to `dokku main`.

    * (Bonus) Integrate Slack notifications using an action like `rtCamp/action-slack-notify` or `slackapi/slack-github-action`, referencing a `SLACK_WEBHOOK` secret containing your Slack Incoming Webhook URL.

6.  **Set up Monitoring (Prometheus & Grafana):**

    * On your EC2 instance, install Node Exporter (port 9100) and run cAdvisor (port 8080) as a Docker container.

    * Install and configure Prometheus (port 9090) to scrape metrics from Node Exporter and cAdvisor.

    * Install and configure Grafana (port 3000).

    * Open ports 3000, 9090, 9100, 8080 in your EC2 security group.

    * Access Grafana, add Prometheus as a data source (`http://localhost:9090`).

    * Import pre-built dashboards for Node Exporter and cAdvisor from grafana.com.

    * Configure an alert rule in Grafana based on a Prometheus query for host CPU usage exceeding 80%.

7.  **Set up Backup Script:**

    * Save the provided `backup.sh` script on your EC2 instance.

    * Make it executable (`chmod +x`).

    * Configure a cron job to run the script daily.

## What Works and What to Improve

**What Works:**

* Successful deployment of a standard WordPress application on a single EC2 instance using Dokku.

* Application is accessible via a custom domain (`devops.s14.michaeloxo.tech`).

* Site is secured with a free Let's Encrypt SSL certificate, enforcing HTTPS.

* Persistent storage is configured for WordPress uploads, plugins, and themes.

* A functional CI/CD pipeline is set up with GitHub Actions, automating deployments on push to the `main` branch.

* Slack notifications are integrated into the CI/CD pipeline for deployment status updates.

* Basic monitoring is established using Prometheus (collecting host and container metrics) and Grafana (visualization).

* An alert for high host CPU usage is configured in Grafana.

* An automated daily backup script for application files and the database is in place.

* A manual rollback strategy using Dokku's `releases:rollback` command is available.

**Areas for Improvement (With More Time):**

* **Automated Rollback in CI/CD:** Enhance the GitHub Actions workflow to automatically trigger a Dokku rollback if post-deployment health checks fail.

* **More Granular Monitoring & Alerting:**

    * Set up more specific alerts (e.g., low disk space on persistent volumes, high database connection errors, high application error rates).

    * Monitor specific WordPress metrics if available (e.g., using a WordPress exporter for Prometheus).

* **Centralized Logging:** Implement a logging solution (e.g., ELK stack, Grafana Loki) to aggregate logs from the Dokku containers for easier debugging and analysis.

* **Offsite Backups:** Implement sending backups to a remote storage location (e.g., S3, Google Cloud Storage) for better disaster recovery.

* **Security Hardening:** Further secure the EC2 instance and Dokku (e.g., fail2ban, stricter firewall rules, regular security patching).

* **High Availability:** For a production environment, consider a multi-server setup with a load balancer and shared storage/database for high availability.

## Screenshots

**Deployed WordPress Application Screenshot:**



**Monitoring Dashboard Screenshot:**

