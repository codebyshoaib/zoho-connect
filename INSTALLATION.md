# Installation & Quick Start Guide

## Installation Steps

1. **Upload Plugin**
   - Zip the entire plugin folder
   - Go to WordPress Admin → Plugins → Add New → Upload Plugin
   - Select the zip file and install

2. **Activate Plugin**
   - After installation, click "Activate Plugin"
   - Ensure CRBS plugin is already installed and active

3. **Configure Settings**
   - Go to **Zoho Flow Bridge** → **Settings**
   - Choose your debug output method:
     - **Console**: Check PHP error_log for output
     - **Admin Page**: View payloads in admin interface
     - **Both**: Output to both locations

4. **Test the Plugin**
   - Create or update a CRBS booking with status ID 2 or 4 (confirmed/accepted)
   - Check your output method:
     - If "Console": Check your server's error_log file
     - If "Admin Page": Go to **Zoho Flow Bridge** → **View Payloads**

## Testing

1. **Create a Test Booking**
   - In CRBS, create a new booking
   - Set status to "Confirmed" or "Accepted" (status IDs 2 or 4)
   - Save the booking

2. **View the Payload**
   - Go to **Zoho Flow Bridge** → **View Payloads**
   - Find your booking in the list
   - Click "View Payload" to see the serialized JSON

3. **Check Console Output** (if enabled)
   - Check your PHP error_log file
   - Look for entries starting with "========== CRBS Booking #"

## Troubleshooting

### Plugin Not Working
- **Check CRBS is Active**: The plugin requires CRBS to be installed and active
- **Check Booking Status**: Only bookings with status IDs 2 or 4 are processed by default
- **Check Logs**: Enable logging and check WordPress debug log

### No Payloads Showing
- **Booking Already Processed**: The plugin prevents duplicate processing
- **To Re-process**: Add `define('QZB_FORCE_RESEND', true);` to wp-config.php (temporarily)
- **Check Booking Status**: Ensure booking status is in allowed list

### Viewing Console Output
- **Local Development**: Check `wp-content/debug.log` if `WP_DEBUG_LOG` is enabled
- **Server Logs**: Check your server's PHP error_log location
- **cPanel**: Usually in `public_html/error_log` or similar

## Next Steps

Once you've verified the payload format is correct:

1. Update `BookingService::process_crbs_booking()` to enable webhook sending
2. Configure your Zoho Flow webhook URL in settings
3. Test with a real booking

## Support

For issues or questions, check:
- WordPress debug log
- PHP error_log
- Plugin logs (if logging enabled)
