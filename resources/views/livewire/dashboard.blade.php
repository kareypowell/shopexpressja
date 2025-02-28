<div>
    <h3 class="mt-5 text-lg leading-6 font-medium text-gray-900">Welcome back, {{ auth()->user()->first_name }}!</h3>

    <div>
        <h3 class="mt-5 text-base font-semibold text-gray-900">Quick Insights</h3>
        <dl class="mt-5 grid grid-cols-1 divide-y divide-gray-200 overflow-hidden rounded-lg bg-white shadow md:grid-cols-3 md:divide-x md:divide-y-0">
            <div class="px-4 py-5 sm:p-6">
                <dt class="text-base font-normal text-gray-900">Packages Shipped</dt>
                <dd class="mt-1 flex items-baseline justify-between md:block lg:flex">
                    <div class="flex items-baseline text-2xl font-semibold text-wax-flower-600">
                        56
                        <span class="ml-2 text-sm font-medium text-gray-500">from 10</span>
                    </div>

                    <div class="inline-flex items-baseline rounded-full bg-green-100 px-2.5 py-0.5 text-sm font-medium text-green-800 md:mt-2 lg:mt-0">
                        <svg class="-ml-1 mr-0.5 size-5 shrink-0 self-center text-green-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
                            <path fill-rule="evenodd" d="M10 17a.75.75 0 0 1-.75-.75V5.612L5.29 9.77a.75.75 0 0 1-1.08-1.04l5.25-5.5a.75.75 0 0 1 1.08 0l5.25 5.5a.75.75 0 1 1-1.08 1.04l-3.96-4.158V16.25A.75.75 0 0 1 10 17Z" clip-rule="evenodd" />
                        </svg>
                        <span class="sr-only"> Increased by </span>
                        12%
                    </div>
                </dd>
            </div>
            <div class="px-4 py-5 sm:p-6">
                <dt class="text-base font-normal text-gray-900">Avg. Open Rate</dt>
                <dd class="mt-1 flex items-baseline justify-between md:block lg:flex">
                    <div class="flex items-baseline text-2xl font-semibold text-wax-flower-600">
                        58.16%
                        <span class="ml-2 text-sm font-medium text-gray-500">from 56.14%</span>
                    </div>

                    <div class="inline-flex items-baseline rounded-full bg-green-100 px-2.5 py-0.5 text-sm font-medium text-green-800 md:mt-2 lg:mt-0">
                        <svg class="-ml-1 mr-0.5 size-5 shrink-0 self-center text-green-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
                            <path fill-rule="evenodd" d="M10 17a.75.75 0 0 1-.75-.75V5.612L5.29 9.77a.75.75 0 0 1-1.08-1.04l5.25-5.5a.75.75 0 0 1 1.08 0l5.25 5.5a.75.75 0 1 1-1.08 1.04l-3.96-4.158V16.25A.75.75 0 0 1 10 17Z" clip-rule="evenodd" />
                        </svg>
                        <span class="sr-only"> Increased by </span>
                        2.02%
                    </div>
                </dd>
            </div>
            
            <div class="px-4 py-5 sm:p-6">
                <dt class="text-base font-normal text-gray-900">Account Balance</dt>
                <dd class="mt-1 flex items-baseline justify-between md:block lg:flex">
                    <div class="flex items-baseline text-2xl font-semibold text-wax-flower-600">
                        $29,648.15 JMD
                        <!-- <span class="ml-2 text-sm font-medium text-gray-500">from 28.62%</span> -->
                    </div>

                    <!-- <div class="inline-flex items-baseline rounded-full bg-red-100 px-2.5 py-0.5 text-sm font-medium text-red-800 md:mt-2 lg:mt-0">
                        <svg class="-ml-1 mr-0.5 size-5 shrink-0 self-center text-red-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
                            <path fill-rule="evenodd" d="M10 3a.75.75 0 0 1 .75.75v10.638l3.96-4.158a.75.75 0 1 1 1.08 1.04l-5.25 5.5a.75.75 0 0 1-1.08 0l-5.25-5.5a.75.75 0 1 1 1.08-1.04l3.96 4.158V3.75A.75.75 0 0 1 10 3Z" clip-rule="evenodd" />
                        </svg>
                        <span class="sr-only"> Decreased by </span>
                        4.05%
                    </div> -->
                </dd>
            </div>
        </dl>
    </div>

    <hr class="my-10">

    <div class="mt-10">

    </div>
</div>