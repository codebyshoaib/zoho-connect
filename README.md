# Zoho Connect Serializer

A WordPress plugin that receives booking payloads from Quantica Labs booking plugin and sends them to Zoho Flow via webhook.

## Architecture

This plugin follows **Domain-Driven Design (DDD)** principles with clear separation of concerns:

### Directory Structure

```
zoho-connect-serializer/
├── zoho-connect-serializer.php    # Main plugin file
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

- ✅ REST API endpoint for receiving booking payloads
- ✅ Payload serialization and transformation
- ✅ Webhook integration with Zoho Flow
- ✅ Retry mechanism for failed webhook requests
- ✅ Comprehensive logging system
- ✅ Admin settings page
- ✅ Dependency injection container
- ✅ Modular, extensible architecture

## Installation

1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure the Zoho Flow webhook URL in Settings → Zoho Connect

## Configuration

Configure the plugin through the WordPress admin:
- **Zoho Flow Webhook URL**: Your Zoho Flow webhook endpoint
- **Enable Logging**: Toggle logging on/off
- **Log Level**: Set minimum log level (debug, info, warning, error)

## API Endpoint

The plugin exposes a REST API endpoint:

```
POST /wp-json/zoho-connect-serializer/v1/booking
```

This endpoint receives booking payloads from the Quantica Labs booking plugin.

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
