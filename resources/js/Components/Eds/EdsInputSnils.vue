<script setup>
import {NInput} from "naive-ui";

const value = defineModel('value`')
</script>

<template>
    <NInput v-model:value="value" v-mask="'###-###-### ##'" placeholder="" />
</template>

<style scoped>

</style>
