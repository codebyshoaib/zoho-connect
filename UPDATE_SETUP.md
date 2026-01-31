# Automatic Update Setup Guide

This plugin uses **Plugin Update Checker** to automatically pull updates from GitHub releases.

## How It Works

1. When you create a **GitHub Release** with a tag (e.g., `v1.0.1`), WordPress will detect it
2. Users see the update notification in their WordPress admin
3. They can update with one click, just like plugins from WordPress.org
4. **No manual zipping or uploading needed!**

## Setup Instructions

### Step 1: Install Dependencies

Run Composer to install the Plugin Update Checker library:

```bash
composer install
```

This will create a `vendor/` directory with the update checker library.

### Step 2: Configure GitHub Repository

Edit `zoho-connect-serializer.php` and update these lines (around line 50):

```php
$github_username = 'codebyshoaib'; // Change to your GitHub username
$github_repo     = 'zoho-connect'; // Change to your repository name
```

### Step 3: Create Your First Release

1. **Bump the version** in `zoho-connect-serializer.php`:
   ```php
   * Version: 1.0.1
   ```
   And update the constant:
   ```php
   define( 'ZOHO_CONNECT_SERIALIZER_VERSION', '1.0.1' );
   ```

2. **Commit and push** your changes:
   ```bash
   git add .
   git commit -m "Version 1.0.1"
   git push origin main
   ```

3. **Create a GitHub Release**:
   - Go to your GitHub repository
   - Click **Releases** → **Create a new release**
   - **Tag version**: `v1.0.1` (must match version format)
   - **Release title**: `Version 1.0.1` (or any title)
   - **Description**: Add release notes
   - Click **Publish release**

### Step 4: Test the Update

1. Install the plugin on a WordPress site (with version 1.0.0)
2. Wait a few minutes (or manually check for updates)
3. Go to **Plugins** page in WordPress admin
4. You should see an update notification for the plugin
5. Click **Update Now** to test the automatic update

## Release Workflow

Every time you want to release an update:

1. ✅ Make your code changes
2. ✅ Update version in `zoho-connect-serializer.php` (header + constant)
3. ✅ Commit and push to GitHub
4. ✅ Create a GitHub Release with matching tag (e.g., `v1.0.2`)
5. ✅ Users will see the update automatically!

## Important Notes

- **Tag Format**: Use semantic versioning like `v1.0.1`, `v1.0.2`, `v2.0.0`
- **Version Match**: The tag should match your plugin version (without the `v` prefix)
- **Release Assets**: The update checker automatically uses the release ZIP from GitHub
- **Update Frequency**: WordPress checks for updates every 12 hours (or when manually triggered)

## Troubleshooting

### Updates Not Showing?

1. **Check version number**: Make sure the version in your plugin file is **lower** than the GitHub release tag
2. **Check tag format**: Use `v1.0.1` format (with `v` prefix)
3. **Clear cache**: WordPress caches update checks - wait 12 hours or clear transients
4. **Check GitHub**: Verify the release exists and is public

### Manual Update Check

To force WordPress to check for updates immediately:

1. Go to **Dashboard** → **Updates**
2. Click **Check Again**

Or use this code snippet (add temporarily to `functions.php`):

```php
delete_site_transient('update_plugins');
wp_update_plugins();
```

## Alternative: Bundle the Library

If you don't want to use Composer, you can bundle the Plugin Update Checker library:

1. Download from: https://github.com/YahnisElsts/plugin-update-checker
2. Extract to: `includes/Infrastructure/Updater/plugin-update-checker/`
3. The updater will automatically detect it

## Security

- Updates are pulled from your **public GitHub repository**
- Only releases you create will be available as updates
- Users can verify the source by checking the repository URL
