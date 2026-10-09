# Moving the demo site to another machine

Git carries the code, config and `settings.php`. This folder carries the rest,
outside git:

| File | What it is |
| --- | --- |
| `dcms.sql.gz` | Database dump: all content (including blog post node 7, "Moving hooks into classes"), users, enabled modules and state |
| `files.tgz` | Uploaded files from `web/sites/default/files` (the Forma default-content images). Generated CSS, JS and image styles are left out; Drupal rebuilds them |

The dump contains user accounts, including the admin password hash. Keep
it out of git and out of public places. Move it by USB stick or a private
cloud folder.

## Before you leave

1. Push every branch and the tag, not just `main`. The stages live on
   `demo-b` to `demo-j`:

   ```sh
   git push --all <remote>
   git push --tags <remote>
   ```

2. If you've changed content since the bundle was made, re-export it from
   the project root:

   ```sh
   ddev drush cr
   ddev export-db --file=demo-assets/dcms.sql.gz
   tar -czf demo-assets/files.tgz -C web/sites/default/files \
     --exclude=./css --exclude=./js --exclude=./php --exclude=./styles .
   ```

3. Copy this folder to the laptop.

## On the laptop

Use DDEV v1.25.4 (the version this was built on) and keep the project name
`dcms`, so the URL stays `https://dcms.ddev.site`. Do all of this before the
conference, because `composer install` and the Docker images need a network
connection.

```sh
git clone <remote> dcms
cd dcms
git switch demo-b && git switch demo-c && git switch demo-d && git switch demo-e \
  && git switch demo-f && git switch demo-g && git switch demo-h && git switch demo-i \
  && git switch demo-j && git switch main   # creates local copies of every stage branch
cp -r /path/to/demo-assets .
ddev start
ddev composer install
ddev import-db --file=demo-assets/dcms.sql.gz
ddev import-files --source=demo-assets/files.tgz
ddev drush cr
```

## Check it worked

```sh
ddev drush config:status        # expect: No differences between DB and sync directory.
ddev drush uli                  # one-time admin login link
```

- `https://dcms.ddev.site/moving-hooks-classes` shows "4 min read" above the content.
- Article pages show their images.
- Switch through every stage once with `stage b` … `stage j` and back to `stage main`.

This exact process was tested on a fresh clone in a separate DDEV project on
2026-10-09. Config was in sync, images and image styles loaded, and every
stage from `main` to `demo-j` passed its page checks.
