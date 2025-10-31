# TalentLMS WordPress Plugin

This plugin integrates TalentLMS with WordPress, allowing you to promote TalentLMS content through a WordPress site. It provides features such as listing TalentLMS courses, displaying course content, and integrating courses as WooCommerce products.

For further documentation, see: [TalentLMS and WordPress](https://epignosis.atlassian.net/wiki/spaces/TL/pages/2790785025/TalentLMS+and+WordPress)

## Directory Structure

```
├── assets/                    # Frontend assets (CSS, JS, images)
├── docker/                    # Docker configuration
├── src/                       # Main plugin source code
│   ├── Helpers/               # Helper classes
│   ├── Pages/                 # Admin page classes
│   ├── Services/              # Service classes
│   ├── TalentLMSLibExt/       # TalentLMS library extensions
│   ├── Validations/           # Validation classes
│   └── [Core plugin files]    # Main plugin PHP files
├── TalentLMSLib/              # TalentLMS PHP library
├── templates/                 # PHP templates
├── tests/                     # Test files
├── talentlms.php              # Main plugin file
├── readme.txt                 # WordPress.org readme
└── composer.json              # PHP dependencies
```

## Deployment

This plugin uses an automated deployment pipeline to publish releases to WordPress.org. The deployment process is managed through GitHub Actions and integrates with WordPress.org's SVN repository system.

### How It Works

1. **Development**: All development happens in this Git repository using standard Git workflows (branches, pull requests, etc.)

2. **Release Process**: When a new version is ready:

   - Version numbers are updated in [talentlms.php](talentlms.php) and [readme.txt](readme.txt)
   - Changes are committed and a Git tag is created (e.g., `v7.1.1`)
   - A GitHub release is published, which triggers the deployment workflow

3. **Automated Deployment**: The [deploy-to-wordpress.yml](.github/workflows/deploy-to-wordpress.yml) workflow automatically:

   - Checks out the code and sets up PHP 8.1
   - Installs production dependencies via Composer
   - Extracts the version number from the Git tag
   - Deploys the code to WordPress.org's SVN repository using the [10up/action-wordpress-plugin-deploy](https://github.com/10up/action-wordpress-plugin-deploy) action
   - Updates both `/trunk` and `/tags/VERSION` in the SVN repository
   - Syncs assets to the WordPress.org plugin directory

4. **WordPress.org Distribution**: WordPress.org automatically:
   - Generates ZIP files for the new version
   - Makes the update available to WordPress installations
   - Notifies users about the new version

### Key Components

- **SVN Credentials**: Stored as GitHub Secrets (`SVN_USERNAME` and `SVN_PASSWORD`)
- **Plugin Slug**: `talentlms` (the WordPress.org directory name)
- **File Exclusions**: Defined in [.distignore](.distignore) to exclude development files (tests, Docker configs, etc.)
- **Manual Deployment**: Can be triggered manually via GitHub Actions workflow_dispatch if needed

### Version Management

This plugin follows semantic versioning (MAJOR.MINOR.PATCH). Version consistency is critical:

- Version in plugin header ([talentlms.php](talentlms.php))
- Stable tag in [readme.txt](readme.txt)
- Git tag name
- SVN tag directory

All four must match for proper deployment and user updates to work correctly.

### SVN Repository Structure

The WordPress.org SVN repository contains:

- `/trunk/` - Latest development version
- `/tags/` - Numbered release versions (e.g., `/tags/7.1.1`)
- `/assets/` - Plugin screenshots, banners, and icons

Users only receive updates from `/tags/`, not `/trunk`, based on the stable tag specified in [readme.txt](readme.txt).
