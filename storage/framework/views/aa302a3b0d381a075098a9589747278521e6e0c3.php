<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>" class="h-full bg-gray-100">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <?php if (! empty(trim($__env->yieldContent('title')))): ?>
    <title><?php echo $__env->yieldContent('title'); ?> - <?php echo e(config('app.name')); ?></title>
    <?php else: ?>
    <title><?php echo e(config('app.name')); ?></title>
    <?php endif; ?>

    <!-- Favicon -->
    <link rel="shortcut icon" href="<?php echo e(url(asset('img/favicon.ico'))); ?>">

    <!-- Fonts -->
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css">

    <!-- Styles -->
    <link rel="stylesheet" href="<?php echo e(url(mix('css/app.css'))); ?>">
    <?php echo \Livewire\Livewire::styles(); ?>


    <!-- Scripts -->
    <script src="<?php echo e(url(mix('js/app.js'))); ?>" defer></script>
    
    <!-- Chart.js for Reports -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Reports Dashboard Scripts -->
    <script src="<?php echo e(url(mix('js/reports.js'))); ?>" defer></script>

    <!-- CSRF Token -->
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.1.4/toastr.min.css">
</head>

<body class="h-full">
    <?php echo $__env->yieldContent('body'); ?>

    <?php echo \Livewire\Livewire::scripts(); ?>


    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    <script>
        window.addEventListener('toastr:success', event => {
            toastr.success(event.detail.message);
        });
        window.addEventListener('toastr:info', event => {
            toastr.info(event.detail.message);
        });
        window.addEventListener('toastr:error', event => {
            toastr.error(event.detail.message);
        });
        window.addEventListener('toastr:warning', event => {
            toastr.warning(event.detail.message);
        });
    </script>

    <?php echo $__env->yieldPushContent('scripts'); ?>
</body>

</html><?php /**PATH /Users/kareypowell/Code/shopexpressja/resources/views/layouts/base.blade.php ENDPATH**/ ?>