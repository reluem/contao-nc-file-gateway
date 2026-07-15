# Contao NC File Gateway

Contao bundle that restores the **file/CSV gateway** removed in Notification Center 2.

In NC 1.x you could append form submissions to a CSV file and optionally attach that file to notification emails. NC 2 dropped this gateway type. This bundle brings it back using the official NC 2 architecture (gateways, parcels, bulky-item vouchers).

**Typical use case:** store every form submission in a CSV under `files/` and send the same CSV as an email attachment to an internal recipient.

## Requirements

- PHP 8.1+
- Contao 4.13.50+ or Contao 5.3+
- `terminal42/notification_center` ^2.6

## Installation

```bash
composer require reluem/contao-nc-file-gateway
```

Then run migrations and clear the cache:

```bash
php vendor/bin/contao-console contao:migrate --no-interaction
php vendor/bin/contao-console cache:clear
```

## Backend setup

### 1. Create a file gateway

**Notification Center → Gateways → New gateway**

| Field | Example |
|-------|---------|
| Type | File/CSV |
| File path | `Intern/Bestellungen/Ballkarten` (relative to `files/`) |

### 2. Add a file message to your notification

**Notification Center → Notifications → your notification → Messages → New message**

| Field | Example |
|-------|---------|
| Gateway | your file gateway |
| File name | `2026_Ballkarten.csv` |
| Storage mode | Append |
| File content | `{{date::d.m.Y H:i}};##form_lastname##;##form_firstname##;##form_email##` |

The **file content** field defines one CSV line per submission. Use semicolons, simple tokens (`##form_*##`), and insert tags (`{{date::…}}`) as needed.

**Important:** sort this message **before** any mailer message in the same notification.

## Email integration

To attach the CSV to an outgoing email in the same notification:

1. Create (or edit) a **mailer** message in the same notification.
2. Set the mailer message **sorting lower** than the file message (file message runs first).
3. In the mailer message language settings, add this token to **Attachment tokens**:

   ```
   ##nc_file_voucher##
   ```

4. Configure recipients, subject, and body as usual.

**Example notification layout:**

| Sorting | Message | Gateway | Purpose |
|---------|---------|---------|---------|
| 1 | Save CSV | File/CSV | Writes/appends the CSV line |
| 2 | Internal notification | Contao Mailer | Sends email with CSV attached |

When the form is submitted, NC processes messages in order:

1. The file message writes the new line to the configured CSV.
2. The mailer message picks up the voucher from step 1 and attaches the current CSV file to the email.

You can combine `##nc_file_voucher##` with other attachment tokens if needed.

## How it works

1. NC creates parcels for all messages in sorting order.
2. For **file** messages, `FileExportListener` appends the CSV line and stores a bulky-item voucher.
3. For **mailer** messages with `##nc_file_voucher##`, `MailerFileAttachmentListener` adds that voucher as an attachment.

## Migration from NC 1.x

If NC 2 already dropped the old `file_*` columns, this bundle's migration re-adds:

- `tl_nc_gateway.file_type`, `tl_nc_gateway.file_path`
- `tl_nc_language.file_name`, `tl_nc_language.file_storage_mode`, `tl_nc_language.file_content`

Existing language records keep their values if the columns were preserved in your database.

## License

LGPL-3.0-or-later
