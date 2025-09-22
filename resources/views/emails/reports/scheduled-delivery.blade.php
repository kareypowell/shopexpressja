@component('mail::message')
# Scheduled Report Ready

Your scheduled **{{ $reportTypeName }}** report has been generated and is ready for download.

## Report Details

**Template:** {{ $template->name }}  
**Type:** {{ $reportTypeName }}  
**Generated:** {{ $generatedAt }}

@if($template->description)
**Description:** {{ $template->description }}
@endif

@component('mail::button', ['url' => $downloadUrl])
Download Report
@endcomponent

## Report Summary

This automated report was generated based on your configured template settings. The report includes data processed according to the template's default filters and configuration.

### What's Included:
- Comprehensive data analysis
- Visual charts and graphs (in PDF format)
- Detailed breakdowns and metrics
- Export-ready format for further analysis

@component('mail::panel')
**Note:** This report link will be available for download for the next 7 days. After that, you can regenerate the report from the reports dashboard.
@endcomponent

If you have any questions about this report or need to modify the scheduled delivery settings, please contact your system administrator or access the reports dashboard.

@component('mail::subcopy')
You received this email because you are subscribed to scheduled reports. To manage your report subscriptions, visit the [Reports Dashboard]({{ route('admin.reports.index') }}).
@endcomponent

Thanks,<br>
{{ config('app.name') }} Reporting System
@endcomponent