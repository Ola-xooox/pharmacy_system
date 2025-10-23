# Dark Mode Integration Guide

## Quick Setup

To add dark mode to any admin portal file, simply add this line after your Tailwind CSS script:

```php
<?php include 'assets/admin_darkmode.php'; ?>
```

## Example Integration

### Before:
```html
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
```

### After:
```html
<script src="https://cdn.tailwindcss.com"></script>
<?php include 'assets/admin_darkmode.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
```

## Files to Update

1. ✅ **dashboard.php** - Updated
2. ✅ **setup_account.php** - Updated
3. ✅ **user_activity_log.php** - Updated  
4. ✅ **delete_account.php** - Updated
5. ✅ **inventory_report.php** - Updated
6. ✅ **sales_report.php** - Updated

**🎉 All admin portal files now have dark mode enabled!**

## Features Included

- **Toggle Button**: Added to header (admin_header.php)
- **Auto Dark Mode**: Respects system preference
- **Local Storage**: Remembers user choice
- **Smooth Transitions**: All elements transition smoothly
- **Chart Support**: Chart.js dark mode integration
- **Accessibility**: Keyboard navigation and ARIA labels

## How It Works

1. **CSS Variables**: Uses CSS custom properties for theming
2. **Tailwind Dark Mode**: Leverages Tailwind's built-in dark mode classes
3. **JavaScript Manager**: Handles toggle logic and persistence
4. **Automatic Styling**: All existing Tailwind classes work automatically

## No Additional Changes Needed

- Your existing HTML/PHP code works as-is
- All Tailwind classes automatically support dark mode
- Forms, tables, cards, and modals adapt automatically
- Charts update dynamically when mode changes

## Testing

1. Open any admin portal page
2. Click the dark mode toggle in the header
3. Verify all elements change to dark theme
4. Refresh page - mode should persist
5. Test on different screen sizes

## Troubleshooting

If dark mode doesn't work:
1. Check if `assets/admin_darkmode.php` exists
2. Ensure it's included after Tailwind CSS
3. Verify no JavaScript errors in console
4. Check browser localStorage for 'admin_dark_mode' key

## Files Structure

```
admin_portal/
├── assets/
│   ├── admin_darkmode.php (Main dark mode system)
│   └── darkmode_template.php (Integration example)
├── admin_header.php (Contains toggle button)
└── [other admin files] (Add include line to each)
```
