# CRBS → Zoho Flow Bridge

A WordPress plugin that serializes QuanticaLabs CRBS booking data and sends to Zoho Flow webhook. Currently in **debug mode** - payloads are output to console/admin page for testing.

## Architecture

This plugin follows **Domain-Driven Design (DDD)** principles with clear separation of concerns:

### Directory Structure

```
crbs-zoho-flow-bridge/
├── zoho-connect-serializer.php    # Main plugin file
├── uninstall.php                  # Uninstall script
├── includes/
│   ├── Autoloader.php              # PSR-4 autoloader
│   ├── Core/
│   │   ├── Plugin.php              # Main plugin class
│   │   ├── Activator.php           # Activation handler
│   │   ├── Deactivator.php         # Deactivation handler
│   │   ├── Config.php              # Configuration manager
│   │   └── DependencyInjection/
│   │       └── Container.php       # DI container
│   ├── Domain/                     # Domain layer (business logic)
│   │   ├── Booking/
│   │   │   ├── Entities/
│   │   │   │   └── BookingPayload.php
│   │   │   ├── Repositories/
│   │   │   │   └── BookingPayloadRepository.php
│   │   │   └── Services/
│   │   │       └── BookingService.php
│   │   ├── CRBS/
│   │   │   └── Integrations/
│   │   │       └── CRBSIntegration.php
│   │   ├── Serialization/
│   │   │   └── Services/
│   │   │       └── SerializationService.php
│   │   └── Webhook/
│   │       └── Services/
│   │           └── ZohoFlowWebhookService.php
│   └── Infrastructure/             # Infrastructure layer
│       ├── API/
│       │   ├── Router.php
│       │   └── Controllers/
│       │       └── BookingController.php
│       ├── Http/
│       │   └── HttpClient.php
│       ├── Debug/
│       │   └── DebugService.php
│       ├── Logging/
│       │   └── Logger.php
│       ├── WordPress/
│       │   └── Hooks/
│       │       └── HookManager.php
│       └── Admin/
│           ├── AdminPage.php
│           └── Settings.php
└── templates/
    └── admin/
        └── settings-page.php
```

## Architecture Layers

### 1. Domain Layer (`includes/Domain/`)
Contains business logic and domain entities:
- **Entities**: Domain objects representing business concepts
- **Repositories**: Data access abstraction
- **Services**: Business logic and orchestration

### 2. Infrastructure Layer (`includes/Infrastructure/`)
Handles technical concerns:
- **API**: REST API routing and controllers
- **Http**: HTTP client for external requests
- **Logging**: Logging functionality
- **WordPress**: WordPress-specific integrations
- **Admin**: Admin interface

### 3. Core Layer (`includes/Core/`)
Core plugin functionality:
- **Plugin**: Main plugin class and service registration
- **Config**: Configuration management
- **DependencyInjection**: Service container

## Features

- ✅ **CRBS Integration**: Automatically hooks into CRBS booking saves
- ✅ **Payload Serialization**: Transforms CRBS booking data to Zoho Flow format
- ✅ **Debug Mode**: Output payloads to console (error_log) or admin page
- ✅ **Admin Interface**: View all processed payloads with JSON display
- ✅ **Webhook Ready**: Infrastructure ready for Zoho Flow webhook (currently disabled in debug mode)
- ✅ **Retry Mechanism**: Built-in retry logic for failed webhook requests
- ✅ **Comprehensive Logging**: Configurable logging system
- ✅ **Dependency Injection**: Clean service container architecture
- ✅ **Modular Design**: Domain-driven design with separation of concerns

## Installation

1. Upload the plugin folder to `/wp-content/plugins/`
2. Ensure **CRBS (Quantica Labs)** plugin is installed and active
3. Activate "CRBS → Zoho Flow Bridge" through the 'Plugins' menu in WordPress
4. Configure settings in **Zoho Flow Bridge** → **Settings**
5. View processed payloads in **Zoho Flow Bridge** → **View Payloads**

## Requirements

- WordPress 5.8+
- PHP 7.4+
- CRBS (Quantica Labs) plugin must be installed and active

## Configuration

Configure the plugin through **Zoho Flow Bridge** → **Settings**:

- **Zoho Flow Webhook URL**: Your Zoho Flow webhook endpoint (optional for now - debug mode)
- **Debug Output Method**: Choose how to output payloads:
  - **Console**: Output to PHP error_log (check your server logs)
  - **Admin Page**: Store payloads for viewing in admin
  - **Both**: Output to both console and admin page
- **Enable Logging**: Toggle logging on/off
- **Log Level**: Set minimum log level (debug, info, warning, error)

## Debug Mode

Currently, the plugin is in **debug mode**. When a CRBS booking is saved:

1. The booking data is captured automatically
2. It's serialized into Zoho Flow format
3. Based on your settings, it's output to:
   - **Console**: Check your PHP error_log file
   - **Admin Page**: View in **Zoho Flow Bridge** → **View Payloads**

To enable actual webhook sending, you'll need to modify the `BookingService::process_crbs_booking()` method.

## How It Works

1. **CRBS Hook**: When a CRBS booking is saved/updated, the plugin automatically captures it
2. **Status Filter**: Only processes bookings with status IDs 2 or 4 (confirmed/accepted) by default
3. **Serialization**: Transforms CRBS booking data into Zoho Flow format:
   - Customer information (name, email, phone)
   - Booking dates (pickup, return)
   - Invoice details (currency, line items)
4. **Output**: Based on settings, outputs to console or admin page
5. **Prevention**: Prevents duplicate processing (can be bypassed with `QZB_FORCE_RESEND` constant)

## Viewing Payloads

- **List View**: Go to **Zoho Flow Bridge** → **View Payloads** to see all processed bookings
- **Detail View**: Click "View Payload" to see the full JSON payload for a specific booking
- **Copy JSON**: Use the "Copy JSON" button to copy the payload for testing

## Development

### Adding New Features

1. **Domain Logic**: Add to `includes/Domain/`
2. **Infrastructure**: Add to `includes/Infrastructure/`
3. **Service Registration**: Register in `Plugin::register_services()`

### Code Standards

- Follow WordPress Coding Standards
- Use PSR-4 autoloading
- Maintain separation of concerns
- Write self-documenting code

## License

GPL-2.0+
