<template>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
        <div>
            <slot name="logo" />
        </div>

        <NCard class="max-w-md mt-4">
            <slot />
        </NCard>
    </div>
</template>
