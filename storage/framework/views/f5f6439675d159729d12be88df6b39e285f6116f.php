<?php $__env->startSection('title', 'Sales & Collections Report'); ?>

<?php $__env->startSection('content'); ?>
    <?php
if (! isset($_instance)) {
    $html = \Livewire\Livewire::mount('reports.report-dashboard', ['type' => 'sales'])->html();
} elseif ($_instance->childHasBeenRendered('LCkawof')) {
    $componentId = $_instance->getRenderedChildComponentId('LCkawof');
    $componentTag = $_instance->getRenderedChildComponentTagName('LCkawof');
    $html = \Livewire\Livewire::dummyMount($componentId, $componentTag);
    $_instance->preserveRenderedChild('LCkawof');
} else {
    $response = \Livewire\Livewire::mount('reports.report-dashboard', ['type' => 'sales']);
    $html = $response->html();
    $_instance->logRenderedChild('LCkawof', $response->id(), \Livewire\Livewire::getRootElementTagName($html));
}
echo $html;
?>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/kareypowell/Code/shopexpressja/resources/views/reports/sales.blade.php ENDPATH**/ ?>